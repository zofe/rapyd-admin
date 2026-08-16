<?php

namespace Zofe\Rapyd\Modules\Companies\Database\Seeders;

use Illuminate\Database\Seeder;
use Zofe\Rapyd\Modules\Companies\Models\Company;

class CompaniesSeeder extends Seeder
{
    public function run(): void
    {
        $numTiers = (int) config('rapyd.companies.tiers', 1);

        if ($numTiers >= 2) {
            $root = Company::firstOrCreate(
                ['tier' => 'tier1', 'parent_id' => null],
                [
                    'business_name' => config('app.name', 'Platform'),
                    'name'          => config('app.name', 'Platform'),
                    'status'        => 'active',
                    'email'         => config('mail.from.address', 'admin@example.com'),
                ]
            );

            Company::firstOrCreate(
                ['tier' => 'tier2', 'parent_id' => $root->id, 'business_name' => 'Demo Tenant'],
                [
                    'name'   => 'Demo Tenant',
                    'status' => 'active',
                ]
            );
        } else {
            Company::firstOrCreate(
                ['tier' => 'tier1', 'parent_id' => null, 'business_name' => 'Demo Company'],
                [
                    'name'   => 'Demo Company',
                    'status' => 'active',
                ]
            );
        }
    }
}
