<?php

namespace Fize\Session\Handler;

use SessionHandler;
use SessionHandlerInterface;

/**
 * Mock
 *
 * 模拟 Session 处理器，在WEB单元测试时很有用。
 */
class Mock extends SessionHandler implements SessionHandlerInterface
{

    /**
     * 构造
     */
    public function __construct()
    {
        global $_SESSION;
        $_SESSION = [];
    }

    /**
     * 打开 session
     * @param string $path 存储会话的路径
     * @param string $name 会话名称
     * @return bool
     */
    public function open($path, $name): bool
    {
        global $_SESSION;
        $_SESSION = [];
        return true;
    }

    /**
     * 关闭 session
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * 读取 Session
     * @param string $id 会话 ID
     * @return string
     */
    public function read($id): string
    {
        global $_SESSION;
        return $_SESSION[$id] ?? '';
    }

    /**
     * 写入 Session
     * @param string $id   会话 ID
     * @param string $data 会话数据
     * @return bool
     */
    public function write($id, $data): bool
    {
        global $_SESSION;
        $_SESSION[$id] = $data;
        return true;
    }

    /**
     * 删除 Session
     * @param string $id 会话 ID
     * @return bool
     */
    public function destroy($id): bool
    {
        global $_SESSION;
        unset($_SESSION[$id]);
        return true;
    }

    /**
     * 垃圾回收 Session
     * @param int $max_lifetime 最长有效时间
     * @return bool
     */
    public function gc($max_lifetime): bool
    {
        return true;
    }
}