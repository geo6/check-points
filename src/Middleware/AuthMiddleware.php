<?php

declare(strict_types=1);

namespace App\Middleware;

use Geo6\Zend\Permissions\Permissions;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Authentication\AuthenticationService;
use Zend\Diactoros\Response\RedirectResponse;

class AuthMiddleware implements MiddlewareInterface
{
    public const AUTH_ATTRIBUTE = 'auth';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);

        $path = $request->getURI()->getPath();

        if (isset($config['authentication']) && ($path !== $config['authentication']['path'])) {
            $permissions = new Permissions(
                $adapter,
                'access',
                $config['authentication']['logfile'],
                $config['authentication']['path']
            );

            if ($permissions->isGranted('check-points') === true) {
                return $handler->handle($request->withAttribute(self::AUTH_ATTRIBUTE, []));
            } else {
                $auth = new AuthenticationService();

                $url = $request->getURI()->getPath();
                if (!empty($request->getURI()->getQuery())) {
                    $url .= '?'.$request->getURI()->getQuery();
                }

                $redirect = $config['authentication']['path'];
                $redirect .= '?redirect_to='.urlencode($url);
                if ($auth->hasIdentity() === false) {
                    $redirect .= '&nologin';
                }

                return new RedirectResponse($redirect);
            }
        }

        return $handler->handle($request->withAttribute(self::AUTH_ATTRIBUTE, null));
    }
}
