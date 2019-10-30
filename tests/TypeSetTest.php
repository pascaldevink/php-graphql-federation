<?php

declare(strict_types=1);

namespace PascalDeVink\GraphQLFederation;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\StringType;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\TestCase;

final class TypeSetTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_filter_types() : void
    {
        $types = [
            new ObjectType(
                [
                    'name' => 'User',
                ]
            ),
            new StringType(),
        ];
        $typeSet = new TypeSet($types);

        $result = $typeSet->filter(function (Type $type) : bool {
            return $type->name === 'User';
        });

        $this->assertCount(1, $result->toArray());
    }

    /**
     * @test
     */
    public function it_should_return_whether_it_has_any_types() : void
    {
        $typeSet = new TypeSet([]);

        $result = $typeSet->hasTypes();

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_should_return_a_list_of_type_names() : void
    {
        $types = [
            new ObjectType(
                [
                    'name' => 'User',
                ]
            ),
            new StringType(),
        ];
        $typeSet = new TypeSet($types);

        $result = $typeSet->getTypeNames();

        $this->assertEquals(['User', 'String'], $result);
    }

    /**
     * @test
     */
    public function it_should_be_represented_as_an_array() : void
    {
        $types = [
            new ObjectType(
                [
                    'name' => 'User',
                ]
            ),
            new StringType(),
        ];
        $typeSet = new TypeSet($types);

        $result = $typeSet->toArray();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }
}
