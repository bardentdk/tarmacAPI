<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure API rate limiting.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api-auth', function (Request $request): array {
            $email = Str::transliterate(Str::lower((string) $request->input('email')));

            return [
                Limit::perMinute(5)->by('api-auth:'.$email.'|'.$request->ip()),
                Limit::perMinute(20)->by('api-auth-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('dtc', fn (Request $request): Limit => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));
    }
}
