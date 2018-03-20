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

class CheckHandler implements RequestHandlerInterface
{
    private $router;
    private $template;

    public function __construct(RouterInterface $router, TemplateRendererInterface $template)
    {
        $this->router = $router;
        $this->template = $template;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);

        $query = $request->getQueryParams();
        $group = [];
        for ($i = 0; $i < count($config['group']); $i++) {
            if (isset($query[$config['group'][$i]])) {
                $group[$config['group'][$i]] = $query[$config['group'][$i]];
            }
        }

        if (is_array($config['label'])) {
            $label = new Expression('concat_ws(\' \', "'.implode('","', $config['label']['table']).'")');
        } else {
            $label = $config['label']['table'];
        }

        $sql = new Sql($adapter, $config['postgresql']['table']);

        $select = $sql->select();
        $select->columns([
            'status',
            'count' => new Expression('COUNT(*)'),
        ]);
        for ($i = 0; $i < count($config['group']); $i++) {
            if (isset($query[$config['group'][$i]])) {
                $select->where->equalTo($config['group'][$i], $query[$config['group'][$i]]);
            }
        }
        $select->group([
            'status',
        ]);

        $qsz = $sql->buildSqlString($select);
        $results = $adapter->query($qsz, $adapter::QUERY_MODE_EXECUTE);

        $stats = [];
        foreach ($results as $r) {
            $stats[$r->status] = $r->count;
        }
        $statistics = [
            'red'        => $stats[-1] ?? 0,
            'orange'     => $stats[0] ?? 0,
            'green'      => $stats[1] ?? 0,
            'red_pct'    => round(($stats[-1] ?? 0) / array_sum($stats) * 100, 1),
            'orange_pct' => round(($stats[0] ?? 0) / array_sum($stats) * 100, 1),
            'green_pct'  => round(($stats[1] ?? 0) / array_sum($stats) * 100, 1),
            'total'      => array_sum($stats),
        ];

        $select = $sql->select();
        $select->columns([
            'id',
            'label'     => $label,
            'label_map' => $config['label']['map'],
            'status',
            'note',
            'update'    => new Expression('hstore_to_json("update")'),
            'the_geog'  => new Expression('ST_AsGeoJSON("the_geog")'),
        ]);
        for ($i = 0; $i < count($config['group']); $i++) {
            if (isset($query[$config['group'][$i]])) {
                $select->where->equalTo($config['group'][$i], $query[$config['group'][$i]]);
            }
        }
        $select->order([
            'status',
            'label',
        ]);

        $qsz = $sql->buildSqlString($select);
        $results = $adapter->query($qsz, $adapter::QUERY_MODE_EXECUTE);

        $json = [
            'type'     => 'FeatureCollection',
            'features' => [],
        ];

        foreach ($results as $r) {
            $json['features'][] = [
                'type'       => 'Feature',
                'id'         => $r->id,
                'properties' => [
                    'color'     => ($r->status === 1 ? 'green' : ($r->status === 0 ? 'orange' : 'red')),
                    'label'     => $r->label,
                    'label_map' => $r->label_map,
                    'status'    => $r->status,
                    'note'      => $r->note,
                    'update'    => (!is_null($r->update) ? json_decode($r->update, true) : null),
                ],
                'geometry' => (!is_null($r->the_geog) ? json_decode($r->the_geog) : null),
            ];
        }

        $data = [
            'title'      => ucwords(substr($config['name'], strpos($config['name'], '/') + 1), '-'),
            'group'      => $group,
            'json'       => $json,
            'statistics' => $statistics,
            'baselayers' => $config['baselayers'],
        ];

        return new HtmlResponse($this->template->render('app::check', $data));
    }
}
