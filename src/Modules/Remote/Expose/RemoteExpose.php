<?php

namespace Zofe\Rapyd\Modules\Remote\Expose;

/**
 * Provider side, in AppServiceProvider::boot():
 *
 *   RemoteExpose::resource('customers', Customer::class)
 *       ->searchable(['name', 'vat_number'])
 *       ->fields(['name', 'vat_number'])
 *       ->label(fn ($c) => $c->name)
 *       ->scope(fn ($query, $user) => $query->where('tenant_id', $user?->tenant_id));
 */
class RemoteExpose
{
    protected static array $resources = [];

    public static function resource(string $name, string $model): ExposedResource
    {
        return static::$resources[$name] = new ExposedResource($model);
    }

    public static function get(string $name): ?ExposedResource
    {
        return static::$resources[$name] ?? null;
    }
}
