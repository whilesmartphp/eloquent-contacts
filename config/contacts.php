<?php

use Whilesmart\Contacts\Models\Contact;

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
    'table' => env('CONTACTS_TABLE', 'contacts'),
];
