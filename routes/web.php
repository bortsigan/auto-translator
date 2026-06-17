<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Serve the OpenAPI spec and a lightweight Swagger UI for API docs.
Route::get('/openapi.yaml', function () {
    return response()->file(base_path('docs/openapi.yaml'), [
        'Content-Type' => 'application/yaml',
    ]);
})->name('openapi.spec');

Route::view('/docs', 'docs')->name('docs');
