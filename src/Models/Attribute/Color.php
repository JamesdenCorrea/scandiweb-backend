<?php
namespace App\Models\Attribute;

use App\Models\Attribute;

class Color extends Attribute
{
    public function getType(): string
    {
        return 'color';
    }

    // You can add color-specific methods here, e.g., validation
}
