<?php

use Zend\Expressive\Authorization;

return [
    'dependencies' => [
        'aliases' => [
            Authorization\AuthorizationInterface::class => Authorization\Acl\ZendAcl::class,
        ],
    ],

    'authorization' => [
        'roles' => [
            'guest' => [],
            'user'  => ['guest'],
            'admin' => ['user'],
        ],
        'resources' => [
            'home',
            'check',
            'api.object',
            'api.statistics',
            'login',
            'logout',
        ],
        'allow' => [
            'guest' => [
                'login',
            ],
            'user'  => [
                'logout',
                'home',
                'check',
                'api.object',
                'api.statistics',
            ],
            'admin' => [
            ],
        ],
    ],
];
