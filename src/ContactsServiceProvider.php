<?php

namespace Whilesmart\Contacts;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Whilesmart\Contacts\Contracts\ResponseFormatter;
use Whilesmart\Contacts\Models\Contact;

class ContactsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/contacts.php', 'contacts');

        $this->app->bind(
            ResponseFormatter::class,
            fn ($app): ResponseFormatter => $app->make(config('contacts.response_formatter')),
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/contacts.php' => config_path('contacts.php'),
        ], 'contacts-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'contacts-migrations');

        $this->publishes([
            __DIR__.'/../database/upgrades/2026_11_18_000001_add_uuids_to_existing_contacts.php' => database_path('migrations/2026_11_18_000001_add_uuids_to_existing_contacts.php'),
        ], 'contacts-upgrade-2');

        Route::model('contact', config('contacts.model', Contact::class));

        if (config('contacts.register_routes', true)) {
            Route::middleware(config('contacts.route_middleware', ['api', 'auth:sanctum']))
                ->prefix(config('contacts.route_prefix', 'api'))
                ->group(__DIR__.'/../routes/api.php');
        }
    }
}
