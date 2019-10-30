<?php

declare(strict_types=1);

namespace PascalDeVink\GraphQLFederation;

use GraphQL\Type\Definition\Type;

final class TypeSet
{
    /**
     * @var Type[]
     */
    private $types;

    /**
     * @param Type[] $types
     */
    public function __construct(array $types)
    {
        $this->types = $types;
    }

    /**
     * @return Type[]
     */
    public function toArray() : array
    {
        return $this->types;
    }

    public function filter(callable $callable) : self
    {
        return new self(array_filter($this->types, $callable));
    }

    public function hasTypes() : bool
    {
        return count($this->types) > 0;
    }

    /**
     * @return string[]
     */
    public function getTypeNames() : array
    {
        return array_map(
            function (Type $type) : string {
                return $type->name;
            },
            $this->types
        );
    }
}
