<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;
use Zend\Expressive\Authentication;
use Zend\Expressive\MiddlewareFactory;

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container) : void {
    $config = $container->get('config');

    $app->route('/app/check-points/login/', [
        App\Handler\LoginHandler::class,
        Authentication\AuthenticationMiddleware::class,
    ], ['GET', 'POST'], 'login');
    $app->get('/app/check-points/logout/', [
        Authentication\AuthenticationMiddleware::class,
        App\Handler\LogoutHandler::class,
    ], 'logout');

    $app->get('/app/check-points/', [
        Authentication\AuthenticationMiddleware::class,
        App\Handler\HomeHandler::class,
    ], 'home');
    $app->get('/app/check-points/check/', [
        Authentication\AuthenticationMiddleware::class,
        App\Handler\CheckHandler::class,
    ], 'check');

    $app->get('/app/check-points/api/object/{id}', [
        Authentication\AuthenticationMiddleware::class,
        App\Handler\API\ObjectHandler::class,
    ], 'api.object');
    $app->put('/app/check-points/api/object/{id}', [
        Authentication\AuthenticationMiddleware::class,
        App\Handler\API\ObjectHandler::class,
    ], 'api.object.put');
    $app->get('/app/check-points/api/statistics/', [
        Authentication\AuthenticationMiddleware::class,
        App\Handler\API\StatisticsHandler::class,
    ], 'api.statistics.put');
};
