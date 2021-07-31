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
     * @param array $config 配置
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
        $value = $this->memcached->get($id);
        if ($this->memcached->getResultCode() === MemcachedDriver::RES_NOTFOUND) {
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
        return $this->memcached->set($id, $data, $this->config['expires']);
    }

    /**
     * 删除 Session
     * @param string $id 会话 ID
     * @return bool
     */
    public function destroy($id): bool
    {
        $result = $this->memcached->delete($id);
        if ($this->memcached->getResultCode() == MemcachedDriver::RES_NOTFOUND) {
            return true;
        }
        return $result;
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
