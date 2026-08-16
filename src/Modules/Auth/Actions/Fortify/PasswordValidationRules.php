<?php

namespace Zofe\Rapyd\Modules\Auth\Actions\Fortify;

use Laravel\Fortify\Rules\Password;

trait PasswordValidationRules
{
    protected function passwordRules(): array
    {
        return ['required', 'string', new Password, 'confirmed'];
    }
}
