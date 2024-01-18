<?php

namespace App\Types\PostMutationTypes\InputTypes;

use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Error\Error;
use GraphQL\Language\AST\StringValueNode;

class JSONScalarType extends ScalarType
{
    public string $name = 'JSONScalarType';

    /**
     * Преобразование данных входных аргументов (при получении от клиента)
     */
    public function serialize($value)
    {
        return json_encode($value);
    }

    /**
     * Преобразование данных, полученных от клиента (при вводе в GraphQL)
     */
    public function parseValue($value)
    {
        return json_decode($value, true);
    }

    /**
     * Преобразование литерала AST-узла входного аргумента
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null)
    {
        // Throw GraphQL\Error\Error vs \UnexpectedValueException to locate the error in the query
        if (!$valueNode instanceof StringValueNode) {
            throw new Error('Query error: Can only parse strings to JSON but got: ' . $valueNode->kind, [$valueNode]);
        }

        return json_decode($valueNode->value, true);
    }
 
}