<?php

$router->get('', App\Controllers\IndexController::class . '@index');

$router->mount('/facility', function () use ($router) {
    $router->get('', App\Controllers\FacilityController::class . '@index');
    $router->get('/{id}', App\Controllers\FacilityController::class . '@show');
    $router->post('', App\Controllers\FacilityController::class . '@store');
    $router->put('/{id}', App\Controllers\FacilityController::class . '@update');
    $router->delete('/{id}', App\Controllers\FacilityController::class . '@destroy');
});