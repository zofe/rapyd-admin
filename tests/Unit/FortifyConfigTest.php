<?php

namespace Zofe\Rapyd\Tests\Unit;

use Laravel\Fortify\Features;
use Zofe\Rapyd\Tests\TestCase;

/**
 * The Fortify config the package publishes: registration follows RAPYD_REGISTRATION, closed when unset.
 * (Features::twoFactorAuthentication() writes its options to config, hence the application TestCase.)
 */
class FortifyConfigTest extends TestCase
{
    protected function features(?string $registration): array
    {
        if ($registration === null) {
            putenv('RAPYD_REGISTRATION');
            unset($_ENV['RAPYD_REGISTRATION'], $_SERVER['RAPYD_REGISTRATION']);
        } else {
            putenv("RAPYD_REGISTRATION={$registration}");
            $_ENV['RAPYD_REGISTRATION'] = $_SERVER['RAPYD_REGISTRATION'] = $registration;
        }

        return (require dirname(__DIR__, 2) . '/config/fortify.php')['features'];
    }

    protected function tearDown(): void
    {
        $this->features(null);
        parent::tearDown();
    }

    public function test_registration_is_closed_unless_asked_for()
    {
        $this->assertNotContains(Features::registration(), $this->features(null), 'closed when RAPYD_REGISTRATION is not set');
        $this->assertNotContains(Features::registration(), $this->features('false'));
        $this->assertContains(Features::registration(), $this->features('true'));
    }

    public function test_the_other_features_stay_whatever_the_setting()
    {
        foreach ([null, 'true'] as $setting) {
            $features = $this->features($setting);
            $this->assertContains(Features::resetPasswords(), $features);
            $this->assertContains(Features::emailVerification(), $features);
            $this->assertContains(Features::twoFactorAuthentication(['confirmPassword' => false]), $features);
            $this->assertSame(array_values($features), $features, 'a plain list, as Fortify expects');
        }
    }
}
