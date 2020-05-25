<?php

namespace fize\session;

use SessionHandler;

/**
 * Session 底层
 */
class Session
{

    /**
     * @var array 配置
     */
    protected static $config;

    /**
     * 初始化
     *
     * 通过调用该构造方法可以进行 session 的初始化
     * @param array $config 配置
     */
    public function __construct(array $config = [])
    {
        $default_config = [
            'cache_expire'      => null,
            'cache_limiter'     => null,
            'module_name'       => null,
            'name'              => null,
            'register_shutdown' => null,
            'save_path'         => null,
            'cookie_params'     => [],
            'save_handler'      => [
                'type'              => '',
                'config'            => [],
                'register_shutdown' => true
            ],
            'options'           => [],
        ];

        $cfg_handler = $default_config['save_handler'];
        if (isset($config['save_handler'])) {
            $cfg_handler = array_merge($cfg_handler, $config['save_handler']);
        }

        $config = array_merge($default_config, $config);
        $config['save_handler'] = $cfg_handler;
        self::$config = $config;

        if (!is_null($config['cache_expire'])) {
            self::cacheExpire($config['cache_expire']);
        }
        if (!is_null($config['cache_limiter'])) {
            self::cacheLimiter($config['cache_limiter']);
        }
        if (!is_null($config['module_name'])) {
            self::moduleName($config['module_name']);
        }
        if (!is_null($config['name'])) {
            self::name($config['name']);
        }
        if ($config['register_shutdown'] === true) {
            self::registerShutdown();
        }
        if (!is_null($config['save_path'])) {
            self::savePath($config['save_path']);
        }
        if (!empty($cookie_params)) {
            $lifetime = (int)$cookie_params['lifetime'];
            $path = isset($cookie_params['path']) ? $cookie_params['path'] : null;
            $domain = isset($cookie_params['domain']) ? $cookie_params['domain'] : null;
            $secure = isset($cookie_params['secure']) ? $cookie_params['secure'] : false;
            $httponly = isset($cookie_params['httponly']) ? $cookie_params['httponly'] : false;
            self::setCookieParams($lifetime, $path, $domain, $secure, $httponly);
        }

        if ($cfg_handler['type'] != '' && $cfg_handler['type'] != 'files') {  //原生模式
            $class = '\\' . __NAMESPACE__ . '\\handler\\' . $cfg_handler['type'];
            $handler = new $class($cfg_handler['config']);
            self::setSaveHandler($handler, $cfg_handler['register_shutdown']);
        }

        if (self::status() == PHP_SESSION_NONE) {
            self::start($config['options']);
        }
    }

    /**
     * 丢弃会话数组更改并完成会话
     * @return bool
     */
    public static function abort()
    {
        return session_abort();
    }

    /**
     * 读取或设置当前缓存的到期时间,以分钟为单位
     * @param int $new_cache_expire 设置缓存到期时间
     * @return int
     */
    public static function cacheExpire($new_cache_expire = null)
    {
        return session_cache_expire($new_cache_expire);
    }

    /**
     * 读取或设置缓存限制器
     * @param string $cache_limiter 缓存限制器的值
     * @return string
     */
    public static function cacheLimiter($cache_limiter = null)
    {
        return session_cache_limiter($cache_limiter);
    }

    /**
     * 创建新会话 ID
     * @param string $prefix 指定前缀
     * @return string
     * @since        PHP7.1
     * @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection
     */
    public static function createId($prefix = null)
    {
        return session_create_id($prefix);
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
     * 执行会话数据垃圾收集
     * @return int 返回回收的会话个数
     * @since        PHP7.1
     * @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection
     */
    public static function gc()
    {
        return session_gc();
    }

    /**
     * 获取当前会话 cookie 参数
     * @return array
     */
    public static function getCookieParams()
    {
        return session_get_cookie_params();
    }

    /**
     * 获取当前会话 ID
     * @return string
     */
    public static function id()
    {
        return session_id();
    }

    /**
     * 获取或设置当前会话模块名称
     * @param string $module 会话模块名称
     * @return string
     */
    public static function moduleName($module = null)
    {
        if (is_null($module)) {
            return session_module_name();
        }
        return session_module_name($module);
    }

    /**
     * 读取或设置会话名称
     * @param string $name 会话名称
     * @return string
     */
    public static function name($name = null)
    {
        if (is_null($name)) {
            return session_name();
        }
        return session_name($name);
    }

    /**
     * 使用新生成的会话 ID 更新现有会话 ID
     * @param bool $delete 是否删除原有的session值对
     * @return bool
     */
    public static function regenerateId($delete = false)
    {
        return session_regenerate_id($delete);
    }

    /**
     * 注册关闭会话
     */
    public static function registerShutdown()
    {
        session_register_shutdown();
    }

    /**
     * 初始化当前会话与原始值数组
     *
     * 使用该方法后，当前对 $_SESSION 的所有操作都将无效
     */
    public static function reset()
    {
        session_reset();
    }

    /**
     * 读取或设置当前会话的保存路径
     * @param string $path 保存路径
     * @return string
     */
    public static function savePath($path = null)
    {
        if (is_null($path)) {
            return session_save_path();
        }
        return session_save_path($path);
    }

    /**
     * 设置会话 cookie 参数
     * @param int    $lifetime Cookie 的生命周期，以秒为单位。
     * @param string $path     cookie 的有效路径
     * @param string $domain   Cookie 的作用域
     * @param bool   $secure   是否仅在使用安全链接时可用
     * @param bool   $httponly 是否使用 httponly 标记
     * @return bool
     */
    public static function setCookieParams($lifetime, $path = null, $domain = null, $secure = false, $httponly = false)
    {
        return session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
    }

    /**
     * 设置用户自定义会话存储函数
     * @param SessionHandler $handler           处理器
     * @param bool           $register_shutdown 是否注册关闭函数
     * @return bool
     */
    public static function setSaveHandler($handler, $register_shutdown = true)
    {
        return session_set_save_handler($handler, $register_shutdown);
    }

    /**
     * 启动新会话或者重用现有会话
     * @param array $options 会话配置
     * @return bool
     */
    public static function start($options = [])
    {
        return session_start($options);
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
     * 释放所有的会话变量
     */
    public static function unset()
    {
        session_unset();
    }

    /**
     * 保存会话数据并结束会话
     * @return bool
     */
    public static function writeClose()
    {
        return session_write_close();
    }
}
