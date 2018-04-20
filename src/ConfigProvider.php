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
            'authentication' => $this->getAuthentication(),
            'dependencies'   => $this->getDependencies(),
            'plates'         => [
                'extensions' => $this->getPlatesExensions(),
            ],
            'templates' => $this->getTemplates(),
        ];
    }

    /**
     * Returns the authentication dependencies.
     */
    public function getAuthentication() : array
    {
        return [
            'username' => 'login',
            'password' => 'password',
            'redirect' => '/app/check-points/login/',
            'logfile'  => 'data/logs/authentication.log',
        ];
    }

    /**
     * Returns the container dependencies.
     */
    public function getDependencies() : array
    {
        return [
            'invokables' => [
                Handler\APIHandler::class => Handler\APIHandler::class,
            ],
            'factories' => [
                Extension\TranslateExtension::class => Extension\Factory\TranslateFactory::class,

                Handler\CheckHandler::class  => Handler\Factory\CheckHandlerFactory::class,
                Handler\HomeHandler::class   => Handler\Factory\HomeHandlerFactory::class,
                Handler\LoginHandler::class  => Handler\Factory\LoginHandlerFactory::class,
                Handler\LogoutHandler::class => Handler\Factory\LogoutHandlerFactory::class,
            ],
        ];
    }

    /**
     * Returns the lates extensions configuration.
     */
    public function getPlatesExensions() : array
    {
        return [
            Extension\TranslateExtension::class,
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
}
