<?php

namespace Zofe\Rapyd\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder;
use Zofe\Rapyd\Tests\Models\User;
use Zofe\Rapyd\Tests\TestCase;

class LegacyRoleRowsTest extends TestCase
{
    use DatabaseMigrations;

    public function test_role_rows_written_with_the_class_name_are_aligned_to_the_morph_alias()
    {
        $this->seed(AuthSeeder::class);
        $admin = User::where('email', 'admin@laravel')->firstOrFail();
        $this->assertTrue($admin->hasRole('admin'));

        // What a v1 install left behind: the FQCN instead of the alias.
        DB::table('model_has_roles')->update(['model_type' => User::class]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertFalse($admin->fresh()->hasRole('admin'), 'legacy rows are invisible to Spatie');

        $migration = require dirname(__DIR__, 2) . '/src/Modules/Auth/Database/Migrations/2026_09_12_000001_align_role_rows_to_the_user_morph_alias.php';
        $migration->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertSame('user', DB::table('model_has_roles')->value('model_type'));
        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }
}
