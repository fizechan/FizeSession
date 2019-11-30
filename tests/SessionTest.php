<?php

use PHPUnit\Framework\TestCase;
use fize\session\Session;
use GuzzleHttp\Client;


class SessionTest extends TestCase
{

    /**
     * @var bool
     */
    protected static $seriver = false;

    /**
     * @var Client
     */
    protected $client;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        if(!self::$seriver) {
            self::$seriver = true;
            $cmd = 'start cmd /k "cd /d %cd%/../examples &&php -S localhost:8123"';
            $pid = popen($cmd, 'r');
            pclose($pid);
            sleep(3);  //待服务器启动
        }

        if(!$this->client) {
            $this->client = new Client([
                'base_uri' => 'http://localhost:8123'
            ]);
        }
    }

    public function testAbort()
    {
        echo '1';
    }

    public function testAction()
    {
        echo '测试Session';
        self::assertNotEmpty(Session::id());
    }
}
