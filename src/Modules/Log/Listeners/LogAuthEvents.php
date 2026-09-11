<?php

namespace Zofe\Rapyd\Modules\Log\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogAuthEvents
{
    public function subscribe($events): array
    {
        $map = [
            Login::class => 'login',
            Logout::class => 'logout',
            Failed::class => 'loginFailed',
        ];
        if (class_exists(\Lab404\Impersonate\Events\TakeImpersonation::class)) {
            $map[\Lab404\Impersonate\Events\TakeImpersonation::class] = 'impersonate';
            $map[\Lab404\Impersonate\Events\LeaveImpersonation::class] = 'impersonateLeave';
        }

        return $map;
    }

    public function login(Login $event): void
    {
        log_activity('login', $event->user, ['ip' => request()->ip()], null, $event->user);
    }

    public function logout(Logout $event): void
    {
        if ($event->user) {
            log_activity('logout', $event->user, [], null, $event->user);
        }
    }

    public function loginFailed(Failed $event): void
    {
        log_activity('login_failed', $event->user, ['email' => $event->credentials['email'] ?? null, 'ip' => request()->ip()], null, false);
    }

    public function impersonate($event): void
    {
        log_activity('impersonate', $event->impersonated, [], null, $event->impersonator);
    }

    public function impersonateLeave($event): void
    {
        log_activity('impersonate_leave', $event->impersonated, [], null, $event->impersonator);
    }
}
