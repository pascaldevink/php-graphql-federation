<?php

declare(strict_types=1);

namespace PascalDeVink\GraphQLFederation;

use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Utils\BuildSchema;
use GraphQL\Utils\SchemaPrinter;
use PHPUnit\Framework\TestCase;

final class FederationTest extends TestCase
{
    /**
     * @var Federation
     */
    private $federation;

    protected function setUp() : void
    {
        $this->federation = new Federation();
    }

    /**
     * @test
     */
    public function it_should_extend_the_schema_with_federation_configuration() : void
    {
        $sdl = <<< EOF
schema {
  query: Query
}

type Query {
  echo(message: String): String
}
EOF;

        $schema = BuildSchema::build($sdl);

        $result = $this->federation->extendSchema($schema);

        $expectedSdl = <<< EOF
directive @external on FIELD_DEFINITION

directive @requires(fields: _FieldSet!) on FIELD_DEFINITION

directive @provides(fields: _FieldSet!) on FIELD_DEFINITION

directive @key(fields: _FieldSet!) on OBJECT | INTERFACE

directive @extends on OBJECT | INTERFACE

type Query {
  echo(message: String): String
  _service: _Service!
}

scalar _FieldSet

type _Service {
  sdl: String
}

EOF;

        $this->assertEquals($expectedSdl, SchemaPrinter::doPrint($result));
    }

    /**
     * @test
     */
    public function it_should_extend_the_schema_with_entities() : void
    {
        $sdl = <<< EOF
schema {
  query: Query
}

type Query {
  echo(message: String): String
}

type User @key(fields: "id") {
  id: ID!
}
EOF;

        $schema = BuildSchema::build($sdl);

        $result = $this->federation->extendSchema($schema);

        $expectedSdl = <<< EOF
directive @external on FIELD_DEFINITION

directive @requires(fields: _FieldSet!) on FIELD_DEFINITION

directive @provides(fields: _FieldSet!) on FIELD_DEFINITION

directive @key(fields: _FieldSet!) on OBJECT | INTERFACE

directive @extends on OBJECT | INTERFACE

type Query {
  echo(message: String): String
  _service: _Service!
  _entities(representations: [_Any!]!): [_Entity]!
}

type User {
  id: ID!
}

scalar _Any

union _Entity = User

scalar _FieldSet

type _Service {
  sdl: String
}

EOF;

        $this->assertEquals($expectedSdl, SchemaPrinter::doPrint($result));
    }

    /**
     * @test
     */
    public function it_should_add_resolvers_to_root_value() : void
    {
        $rootValue = [
            'getUser' => function ($rootValue, $args, $context) {
                return [
                    'id' => 1,
                ];
            },
        ];

        $result = $this->federation->addResolversToRootValue($rootValue);

        $this->assertCount(3, $result);
        $this->assertArrayHasKey('getUser', $result);
        $this->assertArrayHasKey('_service', $result);
        $this->assertArrayHasKey('_entities', $result);
    }

    /**
     * @test
     */
    public function it_should_resolve_the_full_sdl() : void
    {
        $sdl = <<< EOF
schema {
  query: Query
}

type Query {
  echo(message: String): String
}
EOF;

        $schema = $this->federation->extendSchema(BuildSchema::build($sdl));

        $rootValue   = $this->federation->addResolversToRootValue([]);
        $resolveInfo = new ResolveInfo(
            '_service',
            [],
            new NonNull(
                new ObjectType(['name' => '_Service'])
            ),
            new ObjectType(['name' => 'Query']),
            [
                '_service',
            ],
            $schema,
            [],
            $rootValue,
            new OperationDefinitionNode([]),
            []
        );
        $result      = call_user_func($rootValue['_service'], [], [], null, $resolveInfo);

        $expectedSdl = <<< EOF
directive @external on FIELD_DEFINITION

directive @requires(fields: _FieldSet!) on FIELD_DEFINITION

directive @provides(fields: _FieldSet!) on FIELD_DEFINITION

directive @key(fields: _FieldSet!) on OBJECT | INTERFACE

directive @extends on OBJECT | INTERFACE

type Query {
  echo(message: String): String
  _service: _Service!
}

scalar _FieldSet

type _Service {
  sdl: String
}

EOF;

        $this->assertEquals($expectedSdl, $result['sdl']);
    }

    /**
     * @test
     */
    public function it_should_resolve_all_entities() : void
    {
        $sdl = <<< EOF
schema {
  query: Query
}

type Query {
  echo(message: String): String
}

type User {
  id: ID!
}
EOF;

        $schema = $this->federation->extendSchema(BuildSchema::build($sdl));

        $rootValue   = $this->federation->addResolversToRootValue([]);
        $resolveInfo = new ResolveInfo(
            '_entities',
            [],
            new NonNull(
                new ListOfType(
                    new UnionType(['name' => 'Entity'])
                )
            ),
            new ObjectType(['name' => 'Query']),
            [
                '_entities',
            ],
            $schema,
            [],
            $rootValue,
            new OperationDefinitionNode([]),
            [
                'representations' => [
                    [
                        '__typename' => 'User',
                        'id'         => '2',
                    ],
                ],
            ]
        );

        $result      = call_user_func(
            $rootValue['_entities'],
            [],
            ['representations' => [
                [
                    '__typename' => 'User',
                    'id'         => '2',
                ],
            ]],
            null,
            $resolveInfo
        );

        $this->assertEquals([['__typename' => 'User', 'id' => '2']], $result);
    }
}
