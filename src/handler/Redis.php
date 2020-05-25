<?php

namespace fize\session\handler;

use Redis as RedisDriver;
use RuntimeException;
use SessionHandlerInterface;

/**
 * Redis
 *
 * Redis 方式 Session 处理器
 */
class Redis implements SessionHandlerInterface
{

    /**
     * @var array
     */
    private $config;

    /**
     * @var RedisDriver Redis对象
     */
    private $redis;

    /**
     * @var int Session有效时间
     */
    protected $lifeTime = 3600;

    /**
     * 构造
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $default_config = [
            'host'    => '127.0.0.1',
            'port'    => 6379,
            'timeout' => 0,
            'expires' => null
        ];
        $this->config = array_merge($default_config, $config);
        $this->redis = new RedisDriver();
        $result = $this->redis->connect($this->config['host'], $this->config['port'], $this->config['timeout']);
        if (!$result) {
            throw new RuntimeException($this->redis->getLastError());
        }
        if (isset($this->config['password'])) {
            $result = $this->redis->auth($this->config['password']);
            if (!$result) {
                throw new RuntimeException($this->redis->getLastError());
            }
        }
        if (isset($this->config['dbindex'])) {
            $result = $this->redis->select($this->config['dbindex']);
            if (!$result) {
                throw new RuntimeException($this->redis->getLastError());
            }
        }
        $this->redis->setOption(RedisDriver::OPT_SERIALIZER, RedisDriver::SERIALIZER_PHP);
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
        $value = $this->redis->get($session_id);
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
        $expires = $this->config['expires'];
        if ($expires) {
            $result = $this->redis->set($session_id, $session_data, ['ex' => $expires]);
        } else {
            $result = $this->redis->set($session_id, $session_data);
        }
        return $result;
    }

    /**
     * 删除Session
     * @param string $session_id
     * @return bool
     */
    public function destroy($session_id)
    {
        $num = $this->redis->del($session_id);
        return $num !== false;
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
