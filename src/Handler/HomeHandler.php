<?php

declare(strict_types=1);

namespace App\Handler;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Expressive\Router\RouterInterface;
use Zend\Expressive\Template\TemplateRendererInterface;

class HomeHandler implements RequestHandlerInterface
{
    private $containerName;
    private $router;
    private $template;

    public function __construct(RouterInterface $router, TemplateRendererInterface $template, string $containerName)
    {
        $this->containerName = $containerName;
        $this->router = $router;
        $this->template = $template;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);

        if (!is_array($config['group'])) {
            $config['group'] = [$config['group']];
        }

        $query = $request->getQueryParams();
        $group = [];
        for ($i = 0; $i < count($config['group']); $i++) {
            if (isset($query[$config['group'][$i]])) {
                $group[$config['group'][$i]] = $query[$config['group'][$i]];
            } else {
                $current = $config['group'][$i];
                break;
            }
        }

        $sql = new Sql($adapter, $config['postgresql']['table']);

        $select = $sql->select();
        $select->columns([
            'group' => $current,
            'status',
            'count'  => new Expression('COUNT(*)'),
        ]);
        for ($i = 0; $i < count($config['group']); $i++) {
            if (isset($query[$config['group'][$i]])) {
                $select->where->equalTo($config['group'][$i], $query[$config['group'][$i]]);
            }
        }
        $select->where->isNotNull($current);
        $select->group(['group', 'status']);
        $select->order('group');

        $qsz = $sql->buildSqlString($select);
        $results = $adapter->query($qsz, $adapter::QUERY_MODE_EXECUTE);

        $statistics = [];
        foreach ($results as $r) {
            if (!isset($statistics[$r->group])) {
                $statistics[$r->group] = [
                    'red'    => 0,
                    'orange' => 0,
                    'green'  => 0,
                    'total'  => 0,
                ];
            }

            $statistics[$r->group]['total'] += $r->count;

            switch ($r->status) {
                case -1:
                    $statistics[$r->group]['red'] += $r->count;
                    break;
                case 0:
                    $statistics[$r->group]['orange'] += $r->count;
                    break;
                case 1:
                    $statistics[$r->group]['green'] += $r->count;
                    break;
            }
        }

        foreach ($statistics as $k => &$s) {
            $s['red_pct'] = round($s['red'] / $s['total'] * 100, 1);
            $s['orange_pct'] = round($s['orange'] / $s['total'] * 100, 1);
            $s['green_pct'] = round($s['green'] / $s['total'] * 100, 1);
        }

        $data = [
            'title'      => ucwords(substr($config['name'], strpos($config['name'], '/') + 1), '-'),
            'params'     => $request->getQueryParams(),
            'group'      => $group,
            'current'    => $current,
            'last'       => ($current === end($config['group'])),
            'limit'      => $config['limit'] ?? null,
            'statistics' => $statistics,
        ];

        return new HtmlResponse($this->template->render('app::home', $data));
    }
}
