<?php

namespace Jamesdencorrea\ScandiwebBackend\Models;

class ConfigurableProduct extends Product
{
    /** @var Attribute[] */
    protected array $attributes;

    public function __construct(int $id, string $sku, string $name, float $price, array $attributes = [])
    {
        parent::__construct($id, $sku, $name, $price);
        $this->attributes = $attributes;
    }

    public function getType(): string
    {
        return 'configurable';
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
