<?php

namespace fize\session\handler;

use fize\io\Directory;
use fize\io\File as FizeFile;
use SessionHandler;
use SessionHandlerInterface;

/**
 * 文件
 *
 * 文件方式 Session 处理器
 */
class File extends SessionHandler implements SessionHandlerInterface
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
     * @param string $path 存储会话的路径
     * @param string $name 会话名称
     * @return bool
     */
    public function open($path, $name): bool
    {
        $this->savePath = $path;
        return true;
    }

    /**
     * 关闭 session
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
        if (!FizeFile::exists($this->savePath . '/' . $id)) {
            return '';
        }
        $file = new FizeFile($this->savePath . '/' . $id);
        return $file->getContents();
    }

    /**
     * 写入 Session
     * @param string $id   会话 ID
     * @param string $data 会话数据
     * @return bool
     */
    public function write($id, $data): bool
    {
        $file = new FizeFile($this->savePath . '/' . $id, 'w+');
        $file->fwrite($data);
        return true;
    }

    /**
     * 删除 Session
     * @param string $id 会话 ID
     * @return bool
     */
    public function destroy($id): bool
    {
        $file = new FizeFile($this->savePath . '/' . $id);
        $file->delete();
        return true;
    }

    /**
     * 垃圾回收 Session
     * @param int $max_lifetime 最长有效时间
     * @return bool
     */
    public function gc($max_lifetime): bool
    {
        $items = (new Directory($this->savePath))->scan();
        foreach ($items as $item) {
            $a = $this->savePath . '/' . $item;
            if (Directory::exists($a)) {
                continue;
            } else {
                $file = new FizeFile($a);
                $atime = $file->getATime();
                $atgap = time() - $atime;
                $ctime = $file->getCTime();
                $ctgap = time() - $ctime;
                if ($atgap > $max_lifetime && $ctgap > $max_lifetime) {
                    $file->delete();
                }
            }
        }
        return true;
    }
}
