<?php

namespace Zofe\Rapyd\Modules\Auth\Actions\Fortify;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input)
    {
        $userModel = config('auth.providers.users.model');

        Validator::make($input, [
            // registration is open: a display name is plain text, it never carries markup
            'name' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique($userModel)],
            'password' => $this->passwordRules(),
        ])->validate();

        return $userModel::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
