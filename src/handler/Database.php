<?php


namespace fize\session\handler;


use SessionHandler;
use fize\db\Db;


/**
 * 数据库方式Session管理器
 */
class Database extends SessionHandler
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
     * @var \fize\db\definition\Db 实际DB对象
     */
    private $db;

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
        $this->db = Db::connect($config);
    }

    /**
     * 打开session
     * @param string $save_path
     * @param string $session_name
     * @return bool
     */
    public function open($save_path, $session_name)
    {
        $this->db->table($this->config['table']);
        return true;
    }

    /**
     * 关闭session
     * @return bool
     */
    public function close()
    {
        $this->gc($this->lifeTime);
        unset($this->db);
        return true;
    }

    /**
     * 读取Session
     * @param string $session_id
     * @return string
     */
    public function read($session_id)
    {
        $row = $this->db
            ->where([
                'id' => $session_id,
                'expire' => ['>', time()]
            ])
            ->find();
        if($row){
            return $row['data'];
        }else{
            return '';
        }
    }

    /**
     * 写入Session
     * @param string $session_id
     * @param string $session_data
     * @return bool
     */
    public function write($session_id, $session_data)
    {
        $data = [
            'id' => $session_id,
            'expire' => time() + $this->lifeTime,
            'data' => $session_data
        ];
        if($this->db->where(['id' => $session_id])->findOrNull()) {
            $this->db->where(['id' => $session_id])->update($data);
        } else {
            $this->db->insert($data);
        }
        return true;
    }

    /**
     * 删除Session
     * @param string $session_id
     * @return bool
     */
    public function destroy($session_id)
    {
        $this->db
            ->where(['id' => $session_id])
            ->delete();
        return true;
    }

    /**
     * 垃圾回收Session
     * @param int $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime)
    {
        $this->db
            ->where(['expire' => ['<', time()]])
            ->delete();
        return true;
    }

    /**
     * 初始化，如果尚未建立session表，可以运行该方法来建立表
     * 适用于mysql
     * @param array $config
     */
    public static function initMysql(array $config)
    {
        $sql = <<<EOF
CREATE TABLE `{$config['tablename']}`  (
  `id` varchar(190) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `expire` int(11) NOT NULL,
  `data` blob NULL,
  UNIQUE INDEX `id`(`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = 'Session配置' ROW_FORMAT = Compact
EOF;

        $db = Db::connect($config);
        $db->query($sql);
    }
}