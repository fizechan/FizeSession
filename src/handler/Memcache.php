<?php

namespace fize\session\handler;

use Memcache as MemcacheDriver;
use RuntimeException;
use SessionHandlerInterface;

/**
 * Memcache
 *
 * Memcache 方式 Session 处理器
 */
class Memcache implements SessionHandlerInterface
{

    /**
     * @var array
     */
    private $config;

    /**
     * @var MemcacheDriver Memcache对象
     */
    protected $memcache;

    /**
     * 构造
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $default_config = [
            'servers' => [
                ['localhost', 11211, true, 100]
            ],
            'expires' => null
        ];
        $config = array_merge($default_config, $config);
        $this->config = $config;

        $this->memcache = new MemcacheDriver();
        foreach ($this->config['servers'] as $cfg) {
            $host = $cfg[0];
            $port = isset($cfg[1]) ? $cfg[1] : 11211;
            $persistent = isset($cfg[2]) ? $cfg[2] : true;
            $weight = isset($cfg[3]) ? $cfg[3] : 100;
            $result = $this->memcache->addServer($host, $port, $persistent, $weight);
            if (!$result) {
                throw new RuntimeException("Error in addServer {$cfg[0]}.");
            }
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
        $value = $this->memcache->get($session_id);
        if ($value === false) {
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
        return $this->memcache->set($session_id, $session_data, null, $this->config['expires']);
    }

    /**
     * 删除Session
     * @param string $session_id
     * @return bool
     */
    public function destroy($session_id)
    {
        $value = $this->memcache->get($session_id);
        if ($value === false) {
            return true;
        }
        return $this->memcache->delete($session_id);
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
