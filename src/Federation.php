<?php

declare(strict_types=1);

namespace PascalDeVink\GraphQLFederation;

use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaExtender;
use GraphQL\Utils\SchemaPrinter;

final class Federation
{
    public function extendSchema(Schema $schema) {
        $sdl = <<< EOF
scalar _FieldSet

type _Service {
  sdl: String
}

extend type Query {
  _service: _Service!
}

directive @external on FIELD_DEFINITION
directive @requires(fields: _FieldSet!) on FIELD_DEFINITION
directive @provides(fields: _FieldSet!) on FIELD_DEFINITION
directive @key(fields: _FieldSet!) on OBJECT | INTERFACE

directive @extends on OBJECT | INTERFACE
EOF;

        $documentAST = Parser::parse($sdl);

        return $this->extendSchemaWithEntity(SchemaExtender::extend($schema, $documentAST));
    }

    private function extendSchemaWithEntity(Schema $schema) {
        $typeSet = new TypeSet($schema->getTypeMap());

        $entities = $typeSet
            ->filter(function (Type $type) : bool { return $type instanceof ObjectType; })
            ->filter(function (ObjectType $type) : bool { return $type->astNode !== null; })
            ->filter(function (ObjectType $type) : bool {
                /** @var \GraphQL\Language\AST\NodeList $directiveNodes */
                $directiveNodes = $type->astNode->directives;

                return array_reduce(
                    iterator_to_array($directiveNodes->getIterator()),
                    function (bool $carry, DirectiveNode $node) : bool {
                        if ($node->name->kind === 'Name' && $node->name->value === 'key') {
                            return true;
                        }

                        return $carry;
                    },
                    false
                );
            })
        ;

        if ($entities->hasTypes() === false) {
            return $schema;
        }

        $entityTypeNames = implode(' | ', $entities->getTypeNames());

        $sdl = <<< EOF
scalar _Any

union _Entity = $entityTypeNames

extend type Query {
  _entities(representations: [_Any!]!): [_Entity]!
}
EOF;

        $documentAST = Parser::parse($sdl);

        return SchemaExtender::extend($schema, $documentAST);
    }

    public function addResolversToRootValue(array $rootValue) : array {
        $extendedRootValue = [
            '_service' => function($rootValue, $args, $context, ResolveInfo $info) {
                return [
                    'sdl' => SchemaPrinter::doPrint($info->schema),
                ];
            },
            '_entities' => function($rootValue, $args, $context, ResolveInfo $info) {
                $representations = $args['representations'];

                return array_map(
                    function ($representation) use ($info) {
                        $typeName = $representation['__typename'];

                        /** @var ObjectType $type */
                        $type = $info->schema->getType($typeName);

                        if (!$type || $type instanceof ObjectType === false) {
                            throw new \Exception(
                                `The _entities resolver tried to load an entity for type "${$typeName}", but no object type of that name was found in the schema`
                            );
                        }

                        $resolver = $type->resolveFieldFn ?: function () use ($representation) {
                            return $representation;
                        };

                        return $resolver();
                    },
                    $representations
                );
            }
        ];

        return array_merge($rootValue, $extendedRootValue);
    }
}
