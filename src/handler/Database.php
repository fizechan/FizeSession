<?php

namespace fize\session\handler;

use fize\database\core\Db as DbCore;
use fize\database\Db;
use RuntimeException;
use SessionHandlerInterface;

/**
 * 数据库
 *
 * 数据库方式 Session 处理器
 */
class Database implements SessionHandlerInterface
{
    /**
     * @var array 配置
     */
    private $config;

    /**
     * @var DbCore 实际 DB 对象
     */
    private $db;

    /**
     * 构造
     * @param array $config 配置
     */
    public function __construct(array $config = [])
    {
        $default_config = [
            'table' => 'session'
        ];
        $config = array_merge($default_config, $config);
        $this->config = $config;
    }

    /**
     * 打开 session
     * @param string $path 存储会话的路径
     * @param string $name 会话名称
     * @return bool
     */
    public function open($path, $name): bool
    {
        $dbcfg = $this->config['database'];
        $mode = $dbcfg['mode'] ?? null;
        $this->db = Db::connect($dbcfg['type'], $dbcfg['config'], $mode);
        return true;
    }

    /**
     * 关闭 session
     * @return bool
     */
    public function close(): bool
    {
        $this->db = null;
        return true;
    }

    /**
     * 读取 Session
     * @param string $id 会话 ID
     * @return string
     */
    public function read($id): string
    {
        $row = $this->db->table($this->config['table'])->where(['id' => $id])->findOrNull();
        if (!$row) {
            return '';
        }
        $this->db->table($this->config['table'])->where(['id' => $id])->update(['atime' => time()]);
        return $row['data'];
    }

    /**
     * 写入 Session
     * @param string $id   会话 ID
     * @param string $data 会话数据
     * @return bool
     */
    public function write($id, $data): bool
    {
        $row = [
            'id'    => $id,
            'data'  => $data,
            'atime' => time(),
            'ctime' => time()
        ];
        if ($this->db->table($this->config['table'])->where(['id' => $id])->findOrNull()) {
            $this->db->table($this->config['table'])->where(['id' => $id])->update($row);
        } else {
            $this->db->table($this->config['table'])->insert($row);
        }
        return true;
    }

    /**
     * 删除 Session
     * @param string $id 会话 ID
     * @return bool
     */
    public function destroy($id): bool
    {
        $this->db->table($this->config['table'])->where(['id' => $id])->delete();
        return true;
    }

    /**
     * 垃圾回收 Session
     * @param int $max_lifetime 最长有效时间
     * @return bool
     */
    public function gc($max_lifetime): bool
    {
        $map = [
            'atime' => ['<', time() - $max_lifetime],
            'ctime' => ['<', time() - $max_lifetime]
        ];
        $this->db->table($this->config['table'])->where($map)->delete();
        return true;
    }

    /**
     * 初始化
     *
     * 如果尚未建立 session 表，可以运行该方法来建立表
     * @param array $config
     */
    public static function init(array $config)
    {
        switch ($config['database']['type']) {
            case 'mysql':
                $sql = <<<SQL
CREATE TABLE `{$config['table']}`  (
  `id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'ID',
  `data` blob NULL DEFAULT NULL COMMENT '数据',
  `atime` int(10) NOT NULL COMMENT '访问时间',
  `ctime` int(10) NOT NULL COMMENT '生成时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `id`(`id`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '会话' ROW_FORMAT = Dynamic
SQL;
                break;
            default:
                throw new RuntimeException("暂不支持{$config['database']['type']}数据库驱动");
        }
        $mode = $config['database']['mode'] ?? null;
        $db = Db::connect($config['database']['type'], $config['database']['config'], $mode);
        $db->query($sql);
    }
}
