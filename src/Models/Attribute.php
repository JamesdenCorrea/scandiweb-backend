<?php
namespace App\Models;

abstract class Attribute
{
    protected string $name;
    protected string $value;

    public function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    abstract public function getType(): string;

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
