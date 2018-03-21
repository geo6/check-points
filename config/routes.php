<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;
use Zend\Expressive\MiddlewareFactory;

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container) : void {
    $app->get('/app/check-points/login/', App\Handler\LoginHandler::class, 'login');
    $app->post('/app/check-points/login/', App\Handler\LoginHandler::class, 'login.post');

    $app->get('/app/check-points/', App\Handler\HomeHandler::class, 'home');
    $app->get('/app/check-points/check/', App\Handler\CheckHandler::class, 'check');

    $app->get('/app/check-points/api/object/{id}', App\Handler\API\ObjectHandler::class, 'api.object');
    $app->put('/app/check-points/api/object/{id}', App\Handler\API\ObjectHandler::class, 'api.object.put');
    $app->get('/app/check-points/api/statistics/', App\Handler\API\StatisticsHandler::class, 'api.statistics.put');
};
