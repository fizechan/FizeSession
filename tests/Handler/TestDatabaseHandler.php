<?php

namespace Tests\Handler;

use Fize\Session\Handler\DatabaseHandler;
use PHPUnit\Framework\TestCase;

class TestDatabaseHandler extends TestCase
{

    public function testInit()
    {
        $config = [
            'database' => [
                'type'   => 'mysql',
                'mode'   => 'pdo',
                'config' => [
                    'host'     => 'localhost',
                    'user'     => 'root',
                    'password' => '123456',
                    'dbname'   => 'fz_fize'
                ]
            ],
            'table' => 'sys_session'
        ];
        DatabaseHandler::init($config);
        self::assertIsArray($config);
    }
}
