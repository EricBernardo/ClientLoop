<?php

namespace App\Providers;

use App\Mail\Transport\BrevoTransport;
use Carbon\Carbon;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

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
        Mail::extend('brevo', function (array $config): BrevoTransport {
            return new BrevoTransport(
                $this->app->make(HttpFactory::class),
                (string) ($config['key'] ?? ''),
                (int) ($config['timeout'] ?? 30),
            );
        });

        Carbon::setLocale(config('app.locale'));
    }
}
