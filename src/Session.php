<?php
/**
 * Session管理类
 * @author Fize Chan
 * @version V1.0.0.20170111
 */


namespace fize\session;


use SessionHandler;

class Session
{

    /**
     * 禁止实例化
     */
    private function __construct()
    {
    }

    /**
     * 丢弃会话数组更改并完成会话
     */
    public static function abort()
    {
        session_abort();
    }

    /**
     * 返回当前缓存的到期时间,以分钟为单位
     * @return int
     */
    public static function cacheExpire()
    {
        return session_cache_expire();
    }

    /**
     * 读取缓存限制器
     * @return string
     */
    public static function cacheLimiter()
    {
        return session_cache_limiter();
    }

    /**
     * 保存会话数据并结束会话
     */
    public static function writeClose()
    {
        session_write_close();
    }

    /**
     * 解码会话数据
     * @param string $data 编码后的数据
     * @return bool
     */
    public static function decode($data)
    {
        return session_decode($data);
    }

    /**
     * 销毁当前会话中的全部数据
     * @return bool
     */
    public static function destroy()
    {
        return session_destroy();
    }

    /**
     * 将当前会话数据编码为一个字符串
     * @return string
     */
    public static function encode()
    {
        return session_encode();
    }

    /**
     * 获取当前会话cookie参数
     * @return array
     */
    public static function getCookieParams()
    {
        return session_get_cookie_params();
    }

    /**
     * 获取当前会话ID
     * @return string
     */
    public static function id()
    {
        return session_id();
    }

    /**
     * 获取当前会话模块名称
     * @return string
     */
    public static function moduleName()
    {
        return session_module_name();
    }

    /**
     * 使用新生成的会话ID更新现有会话ID
     * @param bool $delete 是否删除原有的session值对
     * @return bool
     */
    public static function regenerateId($delete = false)
    {
        return session_regenerate_id($delete);
    }

    /**
     * 初始化当前会话与原始值数组
     */
    public static function reset()
    {
        session_reset();
    }

    /**
     * 读取当前会话的保存路径
     * @return string
     */
    public static function savePath()
    {
        return session_save_path();
    }

    /**
     * 设置用户自定义会话存储函数
     * @param SessionHandler $handler
     * @param bool $register_shutdown
     * @return bool
     */
    public static function setSaveHandler(SessionHandler $handler, $register_shutdown = true)
    {
        return session_set_save_handler($handler, $register_shutdown);
    }

    /**
     * 启动新会话或者重用现有会话
     * @param bool $start 是否自动开始session
     * @param int $expire 有效时间，以分为单位
     * @param string $module 会话模块名称
     * @param string $name 设置会话名称
     * @param string $id 会话ID
     * @param string $limiter 缓存限制器
     * @param string $path 保存路径
     * @param array $cookie_params 会话 cookie 参数数组
     * @param array $options 要覆盖的配置项数组
     * @return bool
     */
    public static function start($start = true, $expire = null, $module = null, $name = null, $id = null, $limiter = null, $path = null, $cookie_params = [], $options = [])
    {
        if (!empty($limiter)) {
            session_cache_limiter($limiter);
        }
        if (!empty($expire)) {
            session_cache_expire($expire);
        }
        if (!empty($module)) {
            session_module_name($module);
        }
        if (!empty($name)) {
            session_name($name);
        }
        if (!empty($id)) {
            session_id($id);
        }
        if (!is_null($path)) {
            session_save_path($path);
        }
        if (!empty($cookie_params)) {
            $lifetime = (int)$cookie_params['lifetime'];
            //$path = isset($cookie_params['path']) ? $cookie_params['path'] : null;
            $domain = isset($cookie_params['domain']) ? $cookie_params['domain'] : null;
            $secure = isset($cookie_params['secure']) ? $cookie_params['secure'] : false;
            $httponly = isset($cookie_params['httponly']) ? $cookie_params['httponly'] : false;
            session_set_cookie_params($lifetime, $name, $domain, $secure, $httponly);
        }
        $result = true;
        if ($start || session_status() === PHP_SESSION_DISABLED) {
            $result = session_start($options);
        }
        return $result;
    }

    /**
     * 获取当前会话状态
     * @return int
     */
    public static function status()
    {
        return session_status();
    }

    /**
     * 释放当前在内存中已经创建的所有$_SESSION变量
     */
    public static function clear()
    {
        session_unset();
    }

    /**
     * Session初始化
     * @param string $type 指定处理器，为空或者files为使用原生处理器
     * @param array $config 配置项
     * @param bool $register_shutdown
     */
    public static function init($type, array $config = [], $register_shutdown = true)
    {
        if($type == '' || $type == 'files'){  //原生模式
            return;
        }
        $class = '\\fize\\session\\handler\\' . ucfirst($type);
        $handler = new $class($config);
        self::setSaveHandler($handler, $register_shutdown);
    }
}