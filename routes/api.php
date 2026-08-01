<?php

use Illuminate\Support\Facades\Route;

$routes = Route::apiResource('contacts', config('contacts.controller'))
    ->only(config('contacts.route_actions'));

$writeMiddleware = config('contacts.route_write_middleware', []);

if ($writeMiddleware !== []) {
    $routes->middlewareFor(['store', 'update', 'destroy'], $writeMiddleware);
}
