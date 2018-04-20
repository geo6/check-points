<?php

declare(strict_types=1);

namespace App\Handler;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use Geo6\Zend\Log\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Expressive\Authentication\UserInterface;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Session\SessionMiddleware;
use Zend\Expressive\Template\TemplateRendererInterface;

class LoginHandler implements MiddlewareInterface
{
    private $router;
    private $template;

    public function __construct(RouterInterface $router, TemplateRendererInterface $template)
    {
        $this->router = $router;
        $this->template = $template;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        if ($session->has(UserInterface::class)) {
            return new RedirectResponse($this->router->generateUri('home'));
        }

        $query = $request->getQueryParams();

        if ($request->getMethod() === 'POST') {
            $post = $request->getParsedBody();
            $message = '';

            $response = $handler->handle($request);
            if ($response->getStatusCode() !== 302) {
                Log::write(
                    $config['authentication']['logfile'],
                    'Login succeeded ({login}).',
                    [
                        'login'  => $post['login'],
                    ]
                );

                return new RedirectResponse($this->router->generateUri('home'));
            }

            Log::write(
                $config['authentication']['logfile'],
                'Login failed ({login}).',
                [
                    'login'   => $post['login'],
                ],
                \Zend\Log\Logger::WARN
            );

            $message = 'Login failure, please try again.';
        }

        $data = [
            'title'      => ucwords(substr($config['name'], strpos($config['name'], '/') + 1), '-'),
            'params'     => $request->getQueryParams(),
            'message'    => $message ?? null,
            'redirectTo' => $query['redirect_to'] ?? null,
            'noLogin'    => isset($query['nologin']),
        ];

        return new HtmlResponse($this->template->render('app::login', $data));
    }
}
