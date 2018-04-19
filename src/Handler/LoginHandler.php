<?php

declare(strict_types=1);

namespace App\Handler;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use Geo6\Zend\Log\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Zend\Authentication\Adapter\DbTable\CallbackCheckAdapter as AuthAdapter;
use Zend\Authentication\AuthenticationService;
use Zend\Db\Adapter\Adapter as DbAdapter;
use Zend\Db\Sql\TableIdentifier;
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
                return new RedirectResponse('/app/check-points/');
            }

            $message = 'Login Failure, please try again';
/*
            if ($this->post($adapter, $config, $post, $message) === true) {
                if (isset($post['redirect_to'])) {
                    $url = parse_url($post['redirect_to']);

                    if (isset($url['query'])) {
                        parse_str($url['query'], $query);

                        $data = array_merge($query, [
                            'lang' => $post['lang'],
                        ]);
                    } else {
                        $data = [
                            'lang' => $post['lang'],
                        ];
                    }

                    $redirect = $url['path'].'?'.http_build_query($data);
                } else {
                    $redirect = $this->router->generateUri('home').'?'.http_build_query(['lang' => $post['lang']]);
                }

                return new RedirectResponse($redirect);
            }
*/
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

/*
    private function post(DbAdapter $adapter, array $config, array $query, string &$message)
    {
        $auth = new AuthenticationService();

        $authAdapter = new AuthAdapter(
            $adapter,
            new TableIdentifier('user', 'access'),
            'login',
            'password',
            function ($hash, $password) {
                if (password_needs_rehash($hash, PASSWORD_DEFAULT) === true) {
                    Log::write(
                        $config['authentication']['logfile'],
                        'Login "({login})" needs password rehash.',
                        [
                            'login' => $query['login'],
                        ],
                        \Zend\Log\Logger::NOTICE
                    );
                }

                return password_verify($password, $hash);
            }
        );

        $authAdapter
            ->setIdentity($query['login'])
            ->setCredential($query['password']);

        $result = $auth->authenticate($authAdapter);

        if ($result->isValid() !== true) {
            $message = implode(' ; ', $result->getMessages());

            Log::write(
                $config['authentication']['logfile'],
                'Login failed ({login}) : {message}',
                [
                    'login'   => $query['login'],
                    'message' => $message,
                ],
                \Zend\Log\Logger::WARN
            );
        } else {
            $user = $authAdapter->getResultRowObject(['id', 'locked', 'fullname', 'email']);

            if ($user->locked !== false) {
                $message = 'Your account is currently locked.';

                Log::write(
                    $config['authentication']['logfile'],
                    'Login failed ({login}) : {message}',
                    [
                        'login'   => $query['login'],
                        'message' => $message,
                    ],
                    \Zend\Log\Logger::WARN
                );
            } else {
                Log::write(
                    $config['authentication']['logfile'],
                    'Login succeeded ({login}).',
                    [
                        'login' => $query['login'],
                    ]
                );

                return true;
            }
        }

        return false;
    }
*/
}
