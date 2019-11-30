<?php

namespace fize\session\handler;

use SessionHandlerInterface;

/**
 * Memcached 方式
 *
 * Memcached 方式 Session 处理器
 * @todo 待实现
 */
class Memcached implements SessionHandlerInterface
{

    /**
     * @var array
     */
    private $config;

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
        $this->config = $config;
        if(isset($config['expire'])){
            $this->lifeTime = $config['expire'];
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
        return '';
    }

    /**
     * 写入Session
     * @param string $session_id
     * @param string $session_data
     * @return bool
     */
    public function write($session_id, $session_data)
    {
        return true;
    }

    /**
     * 删除Session
     * @param string $session_id
     * @return bool
     */
    public function destroy($session_id)
    {
        return true;
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