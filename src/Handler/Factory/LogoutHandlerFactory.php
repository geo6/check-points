<?php

declare(strict_types=1);

namespace App\Handler\Factory;

use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class LogoutHandlerFactory
{
    public function __invoke(ContainerInterface $container) : MiddlewareInterface
    {
        $router = $container->get(RouterInterface::class);
        $template = $container->get(TemplateRendererInterface::class);

        return new \App\Handler\LogoutHandler($router, $template, get_class($container));
    }
}
