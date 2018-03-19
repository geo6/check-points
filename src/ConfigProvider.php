<?php

declare(strict_types=1);

namespace App;

use Zend\Expressive\Application;

/**
 * The configuration provider for the App module.
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array.
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     *
     * @return array
     */
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * Returns the container dependencies.
     *
     * @return array
     */
    public function getDependencies() : array
    {
        return [
            'delegators' => [
                Application::class => [
                    RoutesDelegator::class,
                ],
            ],
            'invokables' => [
            ],
            'factories'  => [
                Action\APIAction::class => Action\Factory\APIFactory::class,
                Action\CheckAction::class => Action\Factory\CheckFactory::class,
                Action\HomeAction::class  => Action\Factory\HomeFactory::class,
            ],
        ];
    }
}
