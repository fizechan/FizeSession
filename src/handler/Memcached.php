<?php

namespace fize\session\handler;

use Memcached as MemcachedDriver;
use RuntimeException;
use SessionHandlerInterface;

/**
 * Memcached
 *
 * Memcached 方式 Session 处理器
 */
class Memcached implements SessionHandlerInterface
{

    /**
     * @var array
     */
    private $config;

    /**
     * @var MemcachedDriver Memcached对象
     */
    protected $memcached;

    /**
     * 构造
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $default_config = [
            'servers' => [
                ['localhost', 11211, 0]
            ],
            'timeout' => 10,
            'expires' => 0
        ];
        $config = array_merge($default_config, $config);
        $this->config = $config;

        $this->memcached = new MemcachedDriver();
        $result = $this->memcached->addServers($this->config['servers']);
        if (!$result) {
            throw new RuntimeException($this->memcached->getResultMessage(), $this->memcached->getResultCode());
        }
    }

    /**
     * 打开session
     * @param string $save_path
     * @param string $session_name
     * @return bool
     */
    public function open($save_path, $session_name)
    {
        return true;
    }

    /**
     * 关闭session
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * 读取Session
     * @param string $session_id
     * @return string
     */
    public function read($session_id)
    {
        $value = $this->memcached->get($session_id);
        if ($this->memcached->getResultCode() === MemcachedDriver::RES_NOTFOUND) {
            return '';
        }
        return $value;
    }

    /**
     * 写入Session
     * @param string $session_id
     * @param string $session_data
     * @return bool
     */
    public function write($session_id, $session_data)
    {
        return $this->memcached->set($session_id, $session_data, $this->config['expires']);
    }

    /**
     * 删除Session
     * @param string $session_id
     * @return bool
     */
    public function destroy($session_id)
    {
        $result = $this->memcached->delete($session_id);
        if ($this->memcached->getResultCode() == MemcachedDriver::RES_NOTFOUND) {
            return true;
        }
        return $result;
    }

    /**
     * 垃圾回收Session
     * @param int $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return true;
    }

}
