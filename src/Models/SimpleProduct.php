<?php

namespace Jamesdencorrea\ScandiwebBackend\Models;

class SimpleProduct extends Product
{
    public function getType(): string
    {
        return 'simple';
    }

    public function getAttributes(): array
    {
        return []; // No attributes for simple products
    }
}
