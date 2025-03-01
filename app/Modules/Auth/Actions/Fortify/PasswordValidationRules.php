<?php

namespace App\Modules\Auth\Actions\Fortify;

use Laravel\Fortify\Rules\Password;

/**
 * @codeCoverageIgnore
 */
trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array
     */
    protected function passwordRules()
    {
        return ['required', 'string', new Password, 'confirmed'];
    }
}
