<?php
namespace App\Models\Attribute;

use App\Models\Attribute;

class Size extends Attribute
{
    public function getType(): string
    {
        return 'size';
    }

    // You can add size-specific methods here
}
