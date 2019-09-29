<?php

use PHPUnit\Framework\TestCase;
use fize\session\Session;


class SessionTest extends TestCase
{

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        new Session();
        parent::__construct($name, $data, $dataName);
    }

    public function testAction()
    {
        echo '测试Session';
        self::assertNotEmpty(Session::id());
    }
}
