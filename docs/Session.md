# Session

- 命名空间：`Fize\Session`
- 源码：`src/Session.php`
- 依赖：PHP 会话扩展（通常随 PHP 默认启用）
- 返回：[手册目录](index.md)

`Session` 是本库的唯一门面类。它负责合并配置、按需实例化 Handler、启动会话，并把 PHP 原生 `session_*` 函数以静态方法暴露出来。

业务数据仍然读写超全局变量 `$_SESSION`，本类不提供 `get` / `set` 封装。

---

## 职责

1. 在构造时应用缓存、名称、模块、保存路径、自定义 Handler 等配置。
2. 若当前状态为 `PHP_SESSION_NONE`，自动调用 `session_start()`。
3. 以静态方法代理会话生命周期操作。

构造完成后一般不再需要持有实例，后续都通过 `Session::method()` 调用。

---

## 配置

构造函数接收一个关联数组，先与默认值合并。`save_handler` 会单独再合并一层，避免整段覆盖掉默认子项。

```php
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
        'register_shutdown' => true,
    ],
    'options'           => [],
];
```

| 键 | 类型 | 默认 | 说明 |
| --- | --- | --- | --- |
| `cache_expire` | `int\|null` | `null` | 非 `null` 时调用 [`cacheExpire()`](#cacheexpire)，单位为分钟 |
| `cache_limiter` | `string\|null` | `null` | 非 `null` 时调用 [`cacheLimiter()`](#cachelimiter) |
| `module_name` | `string\|null` | `null` | 非 `null` 时调用 [`moduleName()`](#modulename)，用于 PHP 内置模块名 |
| `name` | `string\|null` | `null` | 非 `null` 时调用 [`name()`](#name)，即会话 Cookie 名 |
| `register_shutdown` | `bool\|null` | `null` | 严格等于 `true` 时调用 [`registerShutdown()`](#registershutdown) |
| `save_path` | `string\|null` | `null` | 非 `null` 时调用 [`savePath()`](#savepath) |
| `cookie_params` | `array` | `[]` | **设计上**用于 [`setCookieParams()`](#setcookieparams)，**当前实现未读取**，见 [已知实现问题](#已知实现问题) |
| `save_handler` | `array` | 见上 | 自定义存储，见下一节 |
| `options` | `array` | `[]` | 传给 [`start()`](#start)，即 `session_start($options)` |

`null` 表示“不改 PHP / `php.ini` 的现有值”。

### save_handler

| 键 | 类型 | 默认 | 说明 |
| --- | --- | --- | --- |
| `type` | `string` | `''` | Handler 类型。空字符串或 `files` 时不注册本库处理器 |
| `config` | `array` | `[]` | 原样传给对应 Handler 构造函数 |
| `register_shutdown` | `bool` | `true` | 传给 [`setSaveHandler()`](#setsavehandler) 的第二参数 |

`type` 与类的对应关系：

| `type` | 实际类 | 手册 |
| --- | --- | --- |
| `''` 或 `files` | PHP 原生 `files` | — |
| `File` | `Fize\Session\Handler\FileHandler` | [FileHandler](FileHandler.md) |
| `Database` | `Fize\Session\Handler\DatabaseHandler` | [DatabaseHandler](DatabaseHandler.md) |
| `Redis` | `Fize\Session\Handler\RedisHandler` | [RedisHandler](RedisHandler.md) |
| `Memcached` | `Fize\Session\Handler\MemcachedHandler` | [MemcachedHandler](MemcachedHandler.md) |
| `Memcache` | `Fize\Session\Handler\MemcacheHandler` | [MemcacheHandler](MemcacheHandler.md) |
| `Mock` | `Fize\Session\Handler\MockHandler` | [MockHandler](MockHandler.md) |

拼类规则：

```php
$class = '\\Fize\\Session\\Handler\\' . $type . 'Handler';
$handler = new $class($config);
```

`type` 大小写敏感，必须与类名前缀一致。

### options

`options` 是 PHP `session_start()` 的覆盖项，常见键包括：

| 键 | 含义 |
| --- | --- |
| `gc_maxlifetime` | 会话最长存活秒数 |
| `gc_probability` / `gc_divisor` | 请求结束时触发 GC 的概率 |
| `cookie_lifetime` / `cookie_path` / `cookie_domain` / `cookie_secure` / `cookie_httponly` | 会话 Cookie |
| `cookie_samesite` | SameSite（需 PHP 7.3+） |
| `use_strict_mode` | 严格模式，拒绝未初始化的 ID |
| `read_and_close` | 读取后立即关闭，适合只读请求 |
| `lazy_write` | 数据未变化时不写回 |
| `sid_length` / `sid_bits_per_character` | 会话 ID 形态 |

也可在 `options` 里覆盖 `name`、`save_path` 等，但本库更推荐用顶层配置项，语义更清晰。

---

## 构造方法

```php
public function __construct(array $config = [])
```

执行顺序：

1. 合并默认配置与 `save_handler` 子配置，写入静态属性 `Session::$config`。
2. 依次应用 `cache_expire`、`cache_limiter`、`module_name`、`name`、`register_shutdown`、`save_path`。
3. 本应应用 `cookie_params`，当前不会生效。
4. 当 `save_handler.type` 既不是空字符串也不是 `files` 时，实例化对应 Handler 并 `setSaveHandler()`。
5. `status() === PHP_SESSION_NONE` 时调用 `start($config['options'])`。

```php
use Fize\Session\Session;

new Session([
    'name'         => 'APPSESSID',
    'save_path'    => '/tmp/sessions',
    'save_handler' => [
        'type'   => 'Redis',
        'config' => [
            'host'    => '127.0.0.1',
            'port'    => 6379,
            'expires' => 3600,
        ],
    ],
    'options' => [
        'gc_maxlifetime' => 3600,
    ],
]);
```

若会话已经是 `PHP_SESSION_ACTIVE`（例如前面调用过 `session_start()`），构造函数不会再次启动。

---

## 方法速查

| 方法 | PHP 对应 | 作用 |
| --- | --- | --- |
| [`abort()`](#abort) | `session_abort()` | 丢弃本次更改并结束会话 |
| [`cacheExpire()`](#cacheexpire) | `session_cache_expire()` | 读/写缓存过期分钟数 |
| [`cacheLimiter()`](#cachelimiter) | `session_cache_limiter()` | 读/写缓存限制器 |
| [`createId()`](#createid) | `session_create_id()` | 生成新 ID，不切换当前会话 |
| [`decode()`](#decode) | `session_decode()` | 把编码字符串写入 `$_SESSION` |
| [`destroy()`](#destroy) | `session_destroy()` | 销毁存储中的当前会话 |
| [`encode()`](#encode) | `session_encode()` | 把 `$_SESSION` 编成字符串 |
| [`gc()`](#gc) | `session_gc()` | 执行垃圾回收，返回回收条数 |
| [`getCookieParams()`](#getcookieparams) | `session_get_cookie_params()` | 读取 Cookie 参数 |
| [`id()`](#id) | `session_id()` | **只读**当前会话 ID |
| [`moduleName()`](#modulename) | `session_module_name()` | 读/写会话模块名 |
| [`name()`](#name) | `session_name()` | 读/写会话名称 |
| [`regenerateId()`](#regenerateid) | `session_regenerate_id()` | 轮换会话 ID |
| [`registerShutdown()`](#registershutdown) | `session_register_shutdown()` | 注册关闭时写回 |
| [`reset()`](#reset) | `session_reset()` | 恢复为启动时的会话数据 |
| [`savePath()`](#savepath) | `session_save_path()` | 读/写保存路径 |
| [`setCookieParams()`](#setcookieparams) | `session_set_cookie_params()` | 设置 Cookie 参数 |
| [`setSaveHandler()`](#setsavehandler) | `session_set_save_handler()` | 注册自定义 Handler |
| [`start()`](#start) | `session_start()` | 启动或重用会话 |
| [`status()`](#status) | `session_status()` | 读取会话状态 |
| [`unset()`](#unset) | `session_unset()` | 释放全部会话变量 |
| [`writeClose()`](#writeclose) | `session_write_close()` | 写回并关闭会话 |

除构造函数外全部为 `public static`。

---

## 方法详情

### abort

```php
public static function abort(): bool
```

丢弃本次请求对 `$_SESSION` 的修改，结束会话且不写回存储。存储中仍保留启动时读到的数据。

```php
Session::start();
$_SESSION['count'] = ($_SESSION['count'] ?? 0) + 1;
Session::abort();   // 这次 +1 不会被保存
```

---

### cacheExpire

```php
public static function cacheExpire(int $new_cache_expire = null): int
```

读取或设置会话页面的缓存过期时间，单位为**分钟**。不传参数（或传 `null`）只读取。必须在 [`start()`](#start) 之前设置。

```php
Session::cacheExpire(30);
$minutes = Session::cacheExpire();
```

---

### cacheLimiter

```php
public static function cacheLimiter(string $cache_limiter = null): string
```

读取或设置缓存限制器，影响发送给客户端的 HTTP 缓存头。必须在启动前设置。

常用取值：

| 值 | 含义 |
| --- | --- |
| `nocache` | 禁止缓存（PHP 默认） |
| `public` | 允许公共缓存 |
| `private` | 允许私有缓存 |
| `private_no_expire` | 私有缓存且不发送过期头 |
| `''`（空字符串） | 不发送缓存头 |

```php
Session::cacheLimiter('private');
```

---

### createId

```php
public static function createId(string $prefix = null): string
```

生成一个新的会话 ID，**不会**改变当前会话。可用于需要预先分配 ID 的场景。需要 PHP 7.1+。

```php
$id = Session::createId();
$id = Session::createId('app-');
```

若要让当前请求换用新 ID，应使用 [`regenerateId()`](#regenerateid)。

---

### decode

```php
public static function decode(string $data): bool
```

把 [`encode()`](#encode) 得到的字符串解码并写入当前 `$_SESSION`。会话必须已启动。

```php
Session::start();
$_SESSION['admin'] = ['name' => 'Fize', 'age' => 30];
$data = Session::encode();

Session::destroy();
Session::start();
Session::decode($data);
```

---

### destroy

```php
public static function destroy(): bool
```

删除**存储后端**中的当前会话数据。调用后会话仍处于活动状态，`$_SESSION` 超全局变量也不会自动清空。登出时通常与 [`unset()`](#unset) 一起使用：

```php
Session::start();
Session::unset();
Session::destroy();
```

---

### encode

```php
public static function encode(): string
```

把当前 `$_SESSION` 编码为 PHP 会话序列化字符串。不写存储，也不结束会话。

---

### gc

```php
public static function gc(): int
```

立即执行一次垃圾回收，返回回收的会话个数。需要 PHP 7.1+。

文件、数据库处理器会按访问时间 / 创建时间清理；Redis、Memcached、Memcache 的 `gc()` 恒为成功空操作，过期靠 TTL。

```php
Session::start();
$removed = Session::gc();
```

PHP 仍可能按 `gc_probability` / `gc_divisor` 在请求结束时自动回收。

---

### getCookieParams

```php
public static function getCookieParams(): array
```

返回当前会话 Cookie 参数，键通常包括：

| 键 | 含义 |
| --- | --- |
| `lifetime` | 生命周期（秒），`0` 表示浏览器进程 Cookie |
| `path` | 有效路径 |
| `domain` | 作用域 |
| `secure` | 是否仅 HTTPS |
| `httponly` | 是否 HttpOnly |
| `samesite` | SameSite（PHP 7.3+ 才有） |

```php
Session::start();
$params = Session::getCookieParams();
```

---

### id

```php
public static function id(): string
```

返回当前会话 ID。未启动且未预设时可能为空字符串。

**本方法不能设置 ID。** 原生 `session_id($id)` 的写入能力未被封装。若必须指定 ID，请在启动前直接调用 `session_id($id)`，或启动后使用 [`regenerateId()`](#regenerateid)。

```php
Session::start();
$sid = Session::id();
```

---

### moduleName

```php
public static function moduleName(string $module = null): string
```

读取或设置会话存储模块名。设置必须在启动前完成。

常见值：`files`、`redis`、`memcached`、`user`。注册本库自定义 Handler 后，模块名一般为 `user`。

```php
// 使用 PHP 内置 redis 模块（不是本库 RedisHandler）
Session::moduleName('redis');
Session::savePath('127.0.0.1:6379');
Session::start();
```

`moduleName('redis')` 与 `save_handler.type = 'Redis'` 是两条路线：前者走 PHP 扩展自带的会话模块，后者走本库 [`RedisHandler`](RedisHandler.md)。

---

### name

```php
public static function name(string $name = null): string
```

读取或设置会话名称（Cookie 名）。默认多为 `PHPSESSID`。必须在启动前设置。名称不能只包含数字。

```php
Session::name('APPSESSID');
Session::start();
echo Session::name();
```

---

### regenerateId

```php
public static function regenerateId(bool $delete = false): bool
```

为当前会话生成新 ID。`$delete = true` 时同时删除旧 ID 对应的存储，可降低会话固定攻击风险，但并发请求可能读到空会话。

必须在会话已启动后调用。

```php
Session::start();
$old = Session::id();
Session::regenerateId(true);
$new = Session::id();
```

登录成功后建议轮换一次 ID。

---

### registerShutdown

```php
public static function registerShutdown()
```

将 `session_write_close` 注册为 shutdown 函数，避免某些 SAPI 下会话来不及写回。无返回值。

构造配置里的顶层 `register_shutdown` 只有严格等于 `true` 才会调用本方法；`save_handler.register_shutdown` 是另一条路径，传给 `session_set_save_handler()`。

---

### reset

```php
public static function reset()
```

用启动会话时从存储读到的原始数据重新填充 `$_SESSION`。调用后，当前请求里尚未写回的修改全部作废。无返回值。

```php
Session::start();
$_SESSION['admin'] = ['name' => 'new'];
Session::reset();   // $_SESSION 回到本次请求刚启动时的内容
```

---

### savePath

```php
public static function savePath(string $path = null): string
```

读取或设置保存路径。对 [FileHandler](FileHandler.md) 与 PHP 原生 `files` 来说，这是目录路径；对部分 PHP 内置模块，也可能是连接串（如 `host:port`）。必须在启动前设置。

```php
Session::savePath('/tmp/sessions');
Session::start();
```

[FileHandler](FileHandler.md) 不会自动创建该目录，需保证目录已存在且可写。

---

### setCookieParams

```php
public static function setCookieParams(
    int $lifetime,
    string $path = null,
    string $domain = null,
    bool $secure = false,
    bool $httponly = false
): bool
```

设置会话 Cookie 参数。必须在 [`start()`](#start) 之前调用。本封装对应 PHP 的五参数形式，**没有** `samesite` 参数；如需 SameSite，请通过构造配置的 `options['cookie_samesite']`（PHP 7.3+）或直接调用原生函数。

```php
Session::setCookieParams(3600, '/', 'example.com', true, true);
Session::start();
```

| 参数 | 说明 |
| --- | --- |
| `$lifetime` | 秒；`0` 表示浏览器关闭即失效 |
| `$path` | 有效路径 |
| `$domain` | 作用域 |
| `$secure` | 仅 HTTPS 发送 |
| `$httponly` | 禁止 JavaScript 读取 |

---

### setSaveHandler

```php
public static function setSaveHandler(SessionHandler $handler, bool $register_shutdown = true): bool
```

注册用户自定义存储。参数类型声明为 `SessionHandler`，因此传入对象需继承该类（本库所有 Handler 均满足）。必须在启动前调用。

```php
use Fize\Session\Handler\DatabaseHandler;
use Fize\Session\Session;

$handler = new DatabaseHandler([
    'table'    => 'session',
    'database' => [ /* ... */ ],
]);
Session::setSaveHandler($handler);
Session::start();
```

---

### start

```php
public static function start(array $options = []): bool
```

启动新会话或重用已有会话。成功返回 `true`。重复启动已处于 `PHP_SESSION_ACTIVE` 的会话会失败并产生警告。

```php
Session::start([
    'gc_maxlifetime' => 1440,
    'read_and_close' => false,
]);
```

构造函数会在状态为 `PHP_SESSION_NONE` 时自动调用本方法。

---

### status

```php
public static function status(): int
```

返回当前会话状态：

| 常量 | 值 | 含义 |
| --- | --- | --- |
| `PHP_SESSION_DISABLED` | `0` | 会话功能被禁用 |
| `PHP_SESSION_NONE` | `1` | 已启用但未启动 |
| `PHP_SESSION_ACTIVE` | `2` | 已启动 |

```php
if (Session::status() === PHP_SESSION_NONE) {
    Session::start();
}
```

---

### unset

```php
public static function unset()
```

释放当前 `$_SESSION` 中的全部变量，不删除存储记录。请求正常结束时仍会把空数组写回存储。无返回值。

若要连存储一起删掉，接着调用 [`destroy()`](#destroy)。

---

### writeClose

```php
public static function writeClose(): bool
```

把当前 `$_SESSION` 写回存储并关闭会话。关闭后可尽早释放会话锁，便于并发请求。之后对 `$_SESSION` 的修改不会再自动保存。

```php
Session::start();
$_SESSION['admin'] = ['name' => 'Fize'];
Session::writeClose();
```

脚本结束时 PHP 通常也会自动写回；主动调用可控制时机。

---

## 与 $_SESSION 的关系

| 操作 | 方式 |
| --- | --- |
| 读/写业务数据 | `$_SESSION['key']` |
| 判断键是否存在 | `isset($_SESSION['key'])` |
| 删除单个键 | `unset($_SESSION['key'])` |
| 清空全部键 | `Session::unset()` |
| 导入/导出原始串 | `Session::decode()` / `Session::encode()` |

本类不拦截 `$_SESSION` 的数组访问。

---

## 完整示例

```php
use Fize\Session\Session;

Session::name('APPSESSID');
Session::setCookieParams(86400, '/', null, true, true);
Session::cacheLimiter('nocache');

new Session([
    'save_handler' => [
        'type'   => 'File',
        'config' => [],
    ],
    'save_path' => sys_get_temp_dir() . '/app_sessions',
    'options'   => [
        'gc_maxlifetime' => 86400,
        'use_strict_mode' => '1',
    ],
]);

if (empty($_SESSION['initiated'])) {
    Session::regenerateId(true);
    $_SESSION['initiated'] = true;
}

$_SESSION['last_seen'] = time();
```

---

## 已知实现问题

以下行为来自当前 `src/Session.php`，编写调用代码时需要避开。

### cookie_params 未被读取

构造函数在合并配置后判断的是未赋值的 `$cookie_params`，而不是 `$config['cookie_params']`：

```php
if (!empty($cookie_params)) {
    // ...
    self::setCookieParams($lifetime, $path, $domain, $secure, $httponly);
}
```

因此传入 `'cookie_params' => [...]` 不会生效。请在 `new Session()` 之前或改用手动流程调用 [`setCookieParams()`](#setcookieparams)，或把 Cookie 项放进 `options`。

### 静态配置只写不读

`protected static $config` 在构造时赋值，类内没有读取它的公开方法，运行期不能通过本类回看配置。

### id() 不支持写入

与原生 `session_id()` 不同，本方法没有 `$id` 参数。

---

## 相关章节

- [手册目录](index.md)
- [FileHandler](FileHandler.md)
- [DatabaseHandler](DatabaseHandler.md)
- [RedisHandler](RedisHandler.md)
- [MemcachedHandler](MemcachedHandler.md)
- [MemcacheHandler](MemcacheHandler.md)
- [MockHandler](MockHandler.md)
