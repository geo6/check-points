<?php

declare(strict_types=1);

namespace App\Handler\API;

use App\Middleware\ConfigMiddleware;
use App\Middleware\DbAdapterMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Diactoros\Response\JsonResponse;

class ObjectHandler implements RequestHandlerInterface
{
    private $adapter;
    private $config;

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $this->adapter = $request->getAttribute(DbAdapterMiddleware::DBADAPTER_ATTRIBUTE);
        $this->config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);

        $id = $request->getAttribute('id');

        switch ($request->getMethod()) {
            case 'GET':
                return new JsonResponse($this->get($id));
                break;

            case 'PUT':
                $data = $request->getParsedBody();

                return new JsonResponse($this->put($id, $data));
                break;
        }
    }

    private function get($id)
    {
        $sql = new Sql($this->adapter, $this->config['postgresql']['table']);

        $select = $sql->select();
        $select->where(['id' => $id]);

        $qsz = $sql->buildSqlString($select);
        $query = $this->adapter->query($qsz, $this->adapter::QUERY_MODE_EXECUTE);

        return $query->current();
    }

    private function put($id, $data)
    {
        $sql = new Sql($this->adapter, $this->config['postgresql']['table']);

        $update = $sql->update();

        if (isset($data['longitude'], $data['latitude'])) {
            $update->set([
                'the_geog' => new Expression('ST_SetSRID(ST_MakePoint(?,?), 4326)', [
                    floatval($data['longitude']),
                    floatval($data['latitude']),
                ]),
                'status' => 1,
                'update' => new Expression('hstore(\'datetime\', ?)', [
                    date('c'),
                ]),
            ]);
        }

        if (isset($data['note'])) {
            $update->set([
                'note' => $data['note'],
            ]);
        }

        $update->where(['id' => $id]);

        $qsz = $sql->buildSqlString($update);
        $query = $this->adapter->query($qsz, $this->adapter::QUERY_MODE_EXECUTE);

        return $query->getAffectedRows();
    }
}
