<?php

namespace Jamesdencorrea\ScandiwebBackend\Models\Attribute;

use Jamesdencorrea\ScandiwebBackend\Models\Attribute;

class Length extends Attribute
{
    public function getType(): string
    {
        return 'length';
    }
}
