<?php

declare(strict_types=1);

namespace App\Action\API;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Diactoros\Response\JsonResponse;

class StatisticsAction implements MiddlewareInterface
{
    private $adapter;
    private $config;

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $this->adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $this->config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);

        $data = $request->getQueryParams();

        $sql = new Sql($this->adapter, $this->config['postgresql']['table']);

        $select = $sql->select();
        $select->columns([
            'status',
            'count' => new Expression('COUNT(*)'),
        ]);
        for ($i = 0; $i < count($this->config['group']); $i++) {
            if (isset($data[$this->config['group'][$i]])) {
                $select->where->equalTo($this->config['group'][$i], $data[$this->config['group'][$i]]);
            }
        }
        $select->group([
            'status',
        ]);

        $qsz = $sql->buildSqlString($select);
        $results = $this->adapter->query($qsz, $this->adapter::QUERY_MODE_EXECUTE);

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

        return new JsonResponse($statistics);
    }
}
