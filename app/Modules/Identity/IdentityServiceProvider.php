<?php

declare(strict_types=1);

namespace App\Modules\Identity;

use App\Modules\Identity\Console\CreateAdmin;
use App\Modules\Identity\Console\ResetTwoFactorCommand;
use App\Modules\Identity\Listeners\RecordLastLogin;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Policies\UserPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class IdentityServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([CreateAdmin::class, ResetTwoFactorCommand::class]);
        }

        Gate::policy(User::class, UserPolicy::class);

        Event::listen(Login::class, RecordLastLogin::class);

        Password::defaults(fn (): Password => Password::min(8)->letters()->numbers());
    }
}
