<?php

namespace Whilesmart\Contacts;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Whilesmart\Contacts\Models\Contact;

class ContactsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/contacts.php', 'contacts');
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

        Route::model('contact', config('contacts.model', Contact::class));

        if (config('contacts.register_routes', true)) {
            Route::middleware(config('contacts.route_middleware', ['api', 'auth:sanctum']))
                ->prefix(config('contacts.route_prefix', 'api'))
                ->group(__DIR__.'/../routes/api.php');
        }
    }
}
