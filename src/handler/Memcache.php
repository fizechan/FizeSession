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
     * @param array $config 配置
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
     * 打开 session
     * @param string $save_path    存储会话的路径
     * @param string $session_name 会话名称
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
     * 读取 Session
     * @param string $session_id 会话 ID
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
     * 写入 Session
     * @param string $session_id   会话 ID
     * @param string $session_data 会话数据
     * @return bool
     */
    public function write($session_id, $session_data)
    {
        return $this->memcache->set($session_id, $session_data, null, $this->config['expires']);
    }

    /**
     * 删除 Session
     * @param string $session_id 会话 ID
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
     * 垃圾回收 Session
     * @param int $maxlifetime 最长有效时间
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return true;
    }

}
