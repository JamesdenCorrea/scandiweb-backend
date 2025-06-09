<?php

namespace Jamesdencorrea\ScandiwebBackend\Controller;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class RootQuery extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Query',
            'fields' => [
                'products' => [
                    'type' => Type::listOf(new ProductType()),
                    'resolve' => function () {
                        // For now, static mock data.
                        return [
                            [
                                'id' => 1,
                                'sku' => 'XBOX-123',
                                'name' => 'Xbox Series S',
                                'price' => 299.99,
                                'type' => 'simple',
                                'attributes' => [
                                    [
                                        'name' => 'Color',
                                        'value' => 'White',
                                        'type' => 'color'
                                    ],
                                    [
                                        'name' => 'Capacity',
                                        'value' => '512GB',
                                        'type' => 'capacity'
                                    ]
                                ]
                            ]
                        ];
                    }
                ]
            ]
        ]);
    }
}
