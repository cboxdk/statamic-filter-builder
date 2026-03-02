<?php

namespace Cbox\FilterBuilder;

use Carbon\Carbon;
use Statamic\Facades\Antlers;
use Statamic\Support\Arr;

class VariableParser
{
    /**
     * @param  array<string, mixed>  $params
     * @return list<mixed>|null
     */
    public static function parse(string $variable, array $params = []): ?array
    {
        if (! preg_match('/^\{\{.*}}$/', $variable)) {
            return null;
        }

        try {
            $parsed = (string) Antlers::parse($variable, $params, true);
            if ($parsed === '') {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }

        $decoded = json_decode($parsed, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $decoded = preg_split('/\s*,\s*/', $parsed);
        }

        // array is multidimensional. Don't return it to the query
        if (is_array($decoded) && count($decoded) !== count($decoded, COUNT_RECURSIVE)) {
            return null;
        }

        /** @var list<mixed> */
        return Arr::map(Arr::wrap($decoded), function ($value) {
            return self::castValue($value);
        });
    }

    public static function validate(string $variable): bool
    {
        if (! preg_match('/^\{\{.*}}$/', $variable)) {
            return false;
        }

        try {
            Antlers::parse($variable);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    protected static function castValue(mixed $value): mixed
    {
        if ($value === 1 || $value === '1') {
            return true;
        }

        if ($value === 0 || $value === '0') {
            return false;
        }

        if (is_string($value) && Carbon::canBeCreatedFromFormat($value, 'Y-m-d H:i:s')) {
            return Carbon::createFromFormat('Y-m-d H:i:s', $value);
        }

        if (is_string($value) && Carbon::canBeCreatedFromFormat($value, 'Y-m-d')) {
            /** @var Carbon */
            $date = Carbon::createFromFormat('Y-m-d', $value);

            return $date->startOfDay();
        }

        return $value;
    }
}
