<?php

$router = $di->getShared('router');

require_once '../routes/routes.php';

$router->set404(function() {
    throw new \App\Plugins\Http\Exceptions\NotFound(['error' => 'route not defined']);
});

return $router;