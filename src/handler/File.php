<?php


namespace fize\session\handler;

use SessionHandlerInterface;
use fize\io\File as Fso;
use fize\io\Directory;

/**
 * 文件方式
 *
 * 文件方式 Session 处理器
 */
class File implements SessionHandlerInterface
{
    /**
     * @var array 配置
     */
    private $config;

    /**
     * @var string 存储会话的路径
     */
    private $savePath;

    /**
     * 构造
     * @param array $config 配置
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * 打开 session
     * @param string $save_path 存储会话的路径
     * @param string $session_name 会话名称
     * @return bool
     */
    public function open($save_path, $session_name)
    {
        $this->savePath = $save_path;
        return true;
    }

    /**
     * 关闭 session
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * 读取 Session
     * @param string $session_id
     * @return string
     */
    public function read($session_id)
    {
        if(!Fso::exists($this->savePath . '/' . $session_id)) {
            return '';
        }
        $file = new Fso($this->savePath . '/' . $session_id);
        return $file->getContents();
    }

    /**
     * 写入 Session
     * @param string $session_id 会话 ID
     * @param string $session_data 会话数据
     * @return bool
     */
    public function write($session_id, $session_data)
    {
        $file = new Fso($this->savePath . '/' . $session_id, 'w+');
        $file->open();
        $file->write($session_data);
        return true;
    }

    /**
     * 删除 Session
     * @param string $session_id 会话 ID
     * @return bool
     */
    public function destroy($session_id)
    {
        $file = new Fso($this->savePath . '/' . $session_id);
        $file->delete();
        return true;
    }

    /**
     * 垃圾回收 Session
     * @param int $maxlifetime 最长有效时间
     * @return bool
     */
    public function gc($maxlifetime)
    {
        $items = Directory::scan($this->savePath);
        foreach($items as $item){
            $a = $this->savePath . '/' . $item;
            if(Directory::isDir($a)){
                continue;
            }else{
                $file = new Fso($a);
                $atime = $file->atime();
                $atgap = time() - $atime;
                $ctime = $file->ctime();
                $ctgap = time() - $ctime;
                if($atgap > $maxlifetime && $ctgap > $maxlifetime) {
                    $file->delete();
                }
            }
        }
        return true;
    }
}