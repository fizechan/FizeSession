<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace fize\session\handler;

use Redis as RedisDriver;
use RuntimeException;
use SessionHandler;
use SessionHandlerInterface;

/**
 * Redis
 *
 * Redis 方式 Session 处理器
 */
class Redis extends SessionHandler implements SessionHandlerInterface
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
     * @param array $config 配置
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
        $value = $this->redis->get($id);
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
        $expires = $this->config['expires'];
        if ($expires) {
            $result = $this->redis->set($id, $data, ['ex' => $expires]);
        } else {
            $result = $this->redis->set($id, $data);
        }
        return $result;
    }

    /**
     * 删除 Session
     * @param string $id 会话 ID
     * @return bool
     */
    public function destroy($id): bool
    {
        $num = $this->redis->del($id);
        return $num !== false;
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
