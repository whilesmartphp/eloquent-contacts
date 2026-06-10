<?php

use Illuminate\Support\Facades\Route;
use Whilesmart\Contacts\Http\Controllers\ContactController;

Route::apiResource('contacts', ContactController::class);
