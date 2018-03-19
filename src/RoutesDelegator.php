<?php

declare(strict_types=1);

namespace App;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;

class RoutesDelegator
{
    /**
     * @param ContainerInterface $container
     * @param string             $serviceName Name of the service being created.
     * @param callable           $callback    Creates and returns the service.
     *
     * @return Application
     */
    public function __invoke(ContainerInterface $container, $serviceName, callable $callback) : Application
    {
        /** @var $app Application */
        $app = $callback();

        // Setup routes:
        $app->get('/app/check-points/', Action\HomeAction::class, 'home');
        $app->get('/app/check-points/check/', Action\CheckAction::class, 'check');

        $app->get('/app/check-points/api/object/{id}', Action\API\ObjectAction::class, 'api.object.put');
        $app->put('/app/check-points/api/object/{id}', Action\API\ObjectAction::class, 'api.object.get');
        $app->get('/app/check-points/api/statistics/', Action\API\StatisticsAction::class, 'api.statistics.put');

        return $app;
    }
}
