<?php

namespace Zofe\Rapyd\Tests\Feature;

use ReflectionMethod;
use Zofe\Rapyd\Commands\InstallCommand;
use Zofe\Rapyd\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    protected function inject(string $method, string $content, array $items): string
    {
        $m = new ReflectionMethod(InstallCommand::class, $method);
        $m->setAccessible(true);

        return $m->invoke(new InstallCommand(), $content, $items);
    }

    protected string $legacyUser = <<<'PHP'
<?php

namespace App\Models;

use App\Modules\Auth\Traits\HasRoles;
use App\Modules\Auth\Traits\Impersonate;
use App\Modules\Companies\Traits\HasCompanies;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, HasRoles, Impersonate, HasCompanies;
}
PHP;

    public function test_legacy_imports_with_the_same_short_name_are_replaced_not_duplicated()
    {
        $imports = [
            'use Zofe\Rapyd\Modules\Auth\Traits\HasRoles;',
            'use Zofe\Rapyd\Modules\Auth\Traits\Authorize;',
            'use Zofe\Rapyd\Modules\Auth\Traits\Impersonate;',
            'use Zofe\Rapyd\Modules\Companies\Traits\HasCompanies;',
        ];

        $out = $this->inject('injectImports', $this->legacyUser, $imports);

        $this->assertSame(1, substr_count($out, 'Traits\HasRoles;'), 'one HasRoles import');
        $this->assertStringContainsString('use Zofe\Rapyd\Modules\Auth\Traits\HasRoles;', $out);
        $this->assertStringNotContainsString('use App\Modules\Auth\Traits\HasRoles;', $out);
        $this->assertStringNotContainsString('use App\Modules\Companies\Traits\HasCompanies;', $out);
        $this->assertStringContainsString('use Zofe\Rapyd\Modules\Auth\Traits\Authorize;', $out, 'new imports are added');
        $this->assertStringContainsString('use Illuminate\Notifications\Notifiable;', $out, 'unrelated imports untouched');

        // A syntactically valid file: no duplicate aliases.
        $names = [];
        preg_match_all('/^use .*\\\\(\w+);$/m', $out, $m);
        $this->assertSame(count($m[1]), count(array_unique($m[1])), 'no duplicate short names');
    }

    public function test_trait_uses_are_appended_once()
    {
        $out = $this->inject('injectTraitUses', $this->legacyUser, ['HasRoles', 'Authorize', 'Limit', 'HasCompanies']);

        $this->assertMatchesRegularExpression('/use Notifiable, HasRoles, Impersonate, HasCompanies, Authorize, Limit;/', $out);
        $this->assertSame($out, $this->inject('injectTraitUses', $out, ['HasRoles', 'Authorize', 'Limit']), 'idempotent');
    }

    public function test_a_fresh_user_model_gets_imports_and_traits()
    {
        $fresh = "<?php\n\nnamespace App\\Models;\n\nuse Illuminate\\Foundation\\Auth\\User as Authenticatable;\n\nclass User extends Authenticatable\n{\n}\n";
        $out = $this->inject('injectImports', $fresh, ['use Zofe\Rapyd\Traits\ShortId;']);
        $out = $this->inject('injectTraitUses', $out, ['ShortId']);

        $this->assertStringContainsString("use Illuminate\\Foundation\\Auth\\User as Authenticatable;\nuse Zofe\\Rapyd\\Traits\\ShortId;", $out);
        $this->assertStringContainsString("{\n    use ShortId;\n", $out);
    }
}
