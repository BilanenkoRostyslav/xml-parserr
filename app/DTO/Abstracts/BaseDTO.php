<?php

namespace App\DTO\Abstracts;

use JsonSerializable;
use ReflectionClass;

abstract class BaseDTO implements DTO
{
    public function toArray(): array
    {
        $properties = static::getProperties();
        $result = [];
        array_map(function ($property) use (&$result) {
            $property->setAccessible(true);
            $value = $property->getValue($this);
            $name = $property->getName();
            $result[$name] = $this->normalizeValue($value);
        }, $properties);
        return $result;
    }

    public function getFilteredArray(): array
    {
        return array_filter($this->toArray(), fn($value) => !is_null($value));
    }

    public static function createFromArray(array $data): static
    {
        $reflection = static::getReflection();
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        $arguments = [];
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $arguments[$name] = $data[$name];
        }
        return new static(...$arguments);
    }

    protected static function getReflection(): ReflectionClass
    {
        return new ReflectionClass(static::class);
    }

    protected static function getProperties(): array
    {
        return static::getReflection()->getProperties();
    }

    private function normalizeValue(mixed $value)
    {
        if ($value instanceof DTO) {
            return $value->toArray();
        }
        if (is_array($value)) {
            return array_map(fn($item) => $this->normalizeValue($value), $value);
        }
        return $value;
    }
}