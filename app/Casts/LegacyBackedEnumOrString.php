<?php

namespace App\Casts;

use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/** @implements CastsAttributes<BackedEnum|string, BackedEnum|string> */
class LegacyBackedEnumOrString implements CastsAttributes
{
    /** @param class-string<BackedEnum> $enumClass */
    public function __construct(private string $enumClass) {}

    /** @param array<string, mixed> $attributes */
    public function get(Model $model, string $key, mixed $value, array $attributes): BackedEnum|string
    {
        $stringValue = (string) $value;
        $enum = ($this->enumClass)::tryFrom($stringValue);

        return $enum ?? $stringValue;
    }

    /** @param array<string, mixed> $attributes */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if (is_string($value)) {
            return $value;
        }

        throw new InvalidArgumentException("The {$key} value must be a string or backed enum.");
    }
}
