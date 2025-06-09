<?php
namespace App\Models;

abstract class Product
{
    protected string $id;
    protected string $name;
    protected float $price;
    protected array $attributes = [];

    public function __construct(string $id, string $name, float $price, array $attributes = [])
    {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->attributes = $attributes;
    }

    abstract public function getType(): string;

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
