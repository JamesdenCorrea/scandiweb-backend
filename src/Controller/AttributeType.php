<?php

namespace Jamesdencorrea\ScandiwebBackend\Controller;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class AttributeType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Attribute',
            'fields' => [
                'name' => Type::nonNull(Type::string()),
                'value' => Type::nonNull(Type::string()),
                'type' => Type::nonNull(Type::string()),
            ],
        ]);
    }
}
