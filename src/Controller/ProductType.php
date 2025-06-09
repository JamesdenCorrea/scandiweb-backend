<?php

namespace Jamesdencorrea\ScandiwebBackend\Controller;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ProductType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Product',
            'fields' => [
                'id' => Type::nonNull(Type::int()),
                'sku' => Type::nonNull(Type::string()),
                'name' => Type::nonNull(Type::string()),
                'price' => Type::nonNull(Type::float()),
                'type' => Type::nonNull(Type::string()),
                'attributes' => [
                    'type' => Type::listOf(Type::nonNull(new AttributeType()))
                ]
            ],
        ]);
    }
}
