<?php

namespace Jamesdencorrea\ScandiwebBackend\Models\Attribute;

use Jamesdencorrea\ScandiwebBackend\Models\Attribute;

class Weight extends Attribute
{
    public function getType(): string
    {
        return 'weight';
    }
}
