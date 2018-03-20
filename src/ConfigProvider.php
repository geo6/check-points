<?php

declare(strict_types=1);

namespace App;

/**
 * The configuration provider for the App module.
 *
 * @see https://docs.zendframework.com/zend-component-installer/
 */
class ConfigProvider
{
    /**
     * Returns the configuration array.
     */
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
            'plates'       => [
                'extensions' => $this->getPlatesExentions(),
            ],
        ];
    }

    /**
     * Returns the container dependencies.
     */
    public function getDependencies() : array
    {
        return [
            'invokables' => [
                Handler\APIHandler::class   => Handler\APIHandler::class,
            ],
            'factories'  => [
                Extension\TranslateExtension::class => Extension\Factory\TranslateFactory::class,

                Handler\CheckHandler::class => Handler\Factory\CheckHandlerFactory::class,
                Handler\HomeHandler::class  => Handler\Factory\HomeHandlerFactory::class,
                Handler\LoginHandler::class => Handler\Factory\LoginHandlerFactory::class,
            ],
        ];
    }

    /**
     * Returns the templates configuration.
     */
    public function getTemplates() : array
    {
        return [
            'paths' => [
                'app'           => ['templates/app'],
                'error'         => ['templates/error'],
                'layout'        => ['templates/layout'],
                'partial'       => ['templates/partial'],
                'partial.modal' => ['templates/partial/modal'],
            ],
        ];
    }

    /**
     * Returns the Plates extentsions configuration.
     */
    public function getPlatesExentions() : array
    {
        return [
            Extension\TranslateExtension::class,
        ];
    }
}
