<?php

$router->get('', App\Controllers\IndexController::class . '@index');

$router->mount('/facility', function () use ($router) {
    /**
     * Fetch all facilities (with filters)
     */
    $router->get('', App\Controllers\FacilityController::class . '@index');

    /**
     * Fetch a single facility
     * @param int $id
     */
    $router->get('/{id}', App\Controllers\FacilityController::class . '@show');

    /**
     * Create a new facility
     */
    $router->post('', App\Controllers\FacilityController::class . '@store');

    /**
     * Update a facility
     * @param int $id
     */
    $router->put('/{id}', App\Controllers\FacilityController::class . '@update');

    /**
     * Delete a facility
     * @param int $id
     */
    $router->delete('/{id}', App\Controllers\FacilityController::class . '@destroy');
});