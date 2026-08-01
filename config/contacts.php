<?php

use Whilesmart\Contacts\Http\Controllers\ContactController;
use Whilesmart\Contacts\Http\Resources\ContactResource;
use Whilesmart\Contacts\Models\Contact;
use Whilesmart\Contacts\ResponseFormatters\DefaultResponseFormatter;

return [
    // The Contact model. Override with your own (extending the package model
    // or implementing the same surface) to add fields, casts, or behaviour.
    'model' => Contact::class,

    // Use UUID primary keys (and uuid morph columns) instead of auto-incrementing
    // integers. Must be set before the migration runs. Assumes the owner /
    // contactable models also use UUID keys.
    'uuids' => (bool) env('CONTACTS_UUIDS', false),

    'register_routes' => env('CONTACTS_REGISTER_ROUTES', true),
    'route_prefix' => env('CONTACTS_ROUTE_PREFIX', 'api'),
    'route_middleware' => ['api', 'auth:sanctum'],
    'route_write_middleware' => [],
    'route_actions' => ['index', 'store', 'show', 'update', 'destroy'],
    'controller' => ContactController::class,
    'resource' => ContactResource::class,
    'response_formatter' => DefaultResponseFormatter::class,
    'table' => env('CONTACTS_TABLE', 'contacts'),
];
