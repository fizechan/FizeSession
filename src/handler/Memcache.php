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
            $port = $cfg[1] ?? 11211;
            $persistent = $cfg[2] ?? true;
            $weight = $cfg[3] ?? 100;
            $result = $this->memcache->addServer($host, $port, $persistent, $weight);
            if (!$result) {
                throw new RuntimeException("Error in addServer $cfg[0].");
            }
        }
    }

    /**
     * 打开 session
     * @param string $path 存储会话的路径
     * @param string $name 会话名称
     * @return bool
     */
    public function open($path, $name): bool
    {
        return true;
    }

    /**
     * 关闭session
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
        $value = $this->memcache->get($id);
        if ($value === false) {
            return '';
        }
        return $value;
    }

    /**
     * 写入 Session
     * @param string $id   会话 ID
     * @param string $data 会话数据
     * @return bool
     */
    public function write($id, $data): bool
    {
        return $this->memcache->set($id, $data, null, $this->config['expires']);
    }

    /**
     * 删除 Session
     * @param string $id 会话 ID
     * @return bool
     */
    public function destroy($id): bool
    {
        $value = $this->memcache->get($id);
        if ($value === false) {
            return true;
        }
        return $this->memcache->delete($id);
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
