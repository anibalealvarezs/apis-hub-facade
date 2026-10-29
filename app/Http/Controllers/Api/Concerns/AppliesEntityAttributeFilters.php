<?php

namespace App\Http\Controllers\Api\Concerns;

trait AppliesEntityAttributeFilters
{
    protected function withEntityAttributeFilters(array $payloads, array $validated): array
    {
        foreach (($validated['filters'] ?? []) as $path => $condition) {
            if (!str_starts_with((string)$path, 'attributes.')) continue;
            foreach ($payloads as &$payload) $payload['filters'][$path] = $condition;
            unset($payload);
        }
        return $payloads;
    }
}
