<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;
use Zend\Expressive\Authentication;
use Zend\Expressive\MiddlewareFactory;

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container) : void {
    $config = $container->get('config');

    $routes = [
        ['/app/check-points/', App\Handler\HomeHandler::class, 'GET', 'home'],
        ['/app/check-points/check', App\Handler\CheckHandler::class, 'GET', 'check'],

        ['/app/check-points/api/object/{id}', App\Handler\API\ObjectHandler::class, ['GET','PUT'], 'api.object'],
        ['/app/check-points/api/statistics', App\Handler\API\StatisticsHandler::class, 'GET', 'api.statistics'],
    ];

    if (isset($config['authentication']['pdo'])) {
        $app->route('/app/check-points/login/', [
            App\Handler\LoginHandler::class,
            Authentication\AuthenticationMiddleware::class,
        ], ['GET', 'POST'], 'login');
        $app->get('/app/check-points/logout/', [
            Authentication\AuthenticationMiddleware::class,
            App\Handler\LogoutHandler::class,
        ], 'logout');

        foreach ($routes as $r) {
            if (is_string($r[2])) {
                $r[2] = [$r[2]];
            }

            $app->route($r[0], [
                Authentication\AuthenticationMiddleware::class,
                $r[1]
            ], $r[2], $r[3]);
        }
    } else {
        foreach ($routes as $r) {
            if (is_string($r[2])) {
                $r[2] = [$r[2]];
            }

            $app->route($r[0], $r[1], $r[2], $r[3]);
        }
    }
};
