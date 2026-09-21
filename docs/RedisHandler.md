# RedisHandler

- 命名空间：`Fize\Session\Handler`
- 源码：`src/Handler/RedisHandler.php`
- 会话配置 `type`：`Redis`
- 依赖：`ext-redis`（类 `Redis`）
- 返回：[手册目录](index.md) · [Session](Session.md)

`RedisHandler` 用 Redis 字符串保存会话。连接、鉴权、选库都在构造函数中完成；`open()` / `close()` / `gc()` 为空操作。过期完全依赖写入时的 TTL。

这与 [`Session::moduleName('redis')`](Session.md#modulename) 使用的 PHP 内置 redis 会话模块不是同一条实现路径。

---

## 类声明

```php
class RedisHandler extends SessionHandler implements SessionHandlerInterface
```

---

## 配置

```php
public function __construct(array $config = [])
```

默认值：

```php
[
    'host'    => '127.0.0.1',
    'port'    => 6379,
    'timeout' => 0,
    'expires' => null,
]
```

| 键 | 类型 | 默认 | 说明 |
| --- | --- | --- | --- |
| `host` | `string` | `127.0.0.1` | Redis 主机 |
| `port` | `int` | `6379` | 端口 |
| `timeout` | `float\|int` | `0` | `Redis::connect()` 超时，`0` 表示不限制 |
| `password` | `string` | 未设置 | 存在该键时调用 `auth()` |
| `dbindex` | `int` | 未设置 | 存在该键时调用 `select()` |
| `expires` | `int\|null` | `null` | 写入 TTL（秒）。有值时 `SET key value EX expires`；否则永不过期 |

构造过程：

1. `new Redis()` 后 `connect(host, port, timeout)`，失败抛出 `RuntimeException`（消息为 `getLastError()`）。
2. 若配置了 `password`，`auth()` 失败同样抛 `RuntimeException`。
3. 若配置了 `dbindex`，`select()` 失败同样抛 `RuntimeException`。
4. 执行 `setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP)`。

因此 Redis 不可达时，`new Session([...])` 会在实例化 Handler 阶段直接失败，而不是等到 `session_start()`。

类中还有受保护属性 `$lifeTime = 3600`，**未被任何方法使用**。真正生效的过期时间是 `expires`。

---

## 键与序列化

- Redis 键：会话 ID 本身，无前缀。
- PHP 传给 `write()` 的 `$data` 已是会话编码字符串。
- 本类又开启了 `SERIALIZER_PHP`，因此 Redis 里实际多包一层 PHP `serialize()`。`read()` 时扩展会先反序列化再交给 PHP 会话引擎。

不要和其他未开 `OPT_SERIALIZER` 的客户端共用同一批键，否则读到的内容会不一致。生产环境建议用独立 `dbindex` 或在应用层自行加前缀（本类不支持前缀配置）。

---

## 方法

### open / close

```php
public function open($path, $name): bool
public function close(): bool
```

忽略参数，不关闭 Redis 连接，均返回 `true`。连接生命周期与 Handler 实例相同。

### read

```php
public function read($id): string
```

`get($id)`。键不存在或失败时 Redis 扩展返回 `false`，本方法转换为 `''`。

### write

```php
public function write($id, $data): bool
```

- `expires` 为真值：`set($id, $data, ['ex' => $expires])`
- 否则：`set($id, $data)`，无 TTL

返回 Redis `set` 的布尔结果。`expires` 为 `0` 或 `null` 时走无 TTL 分支。

### destroy

```php
public function destroy($id): bool
```

`del($id)`，返回 `$num !== false`。删除数为 `0`（键本就不存在）时仍视为成功。

### gc

```php
public function gc($max_lifetime): bool
```

恒返回 `true`。过期键由 Redis 按 TTL 淘汰；若 `expires` 未配置，键会一直保留，需要自行清理。

---

## 使用示例

### 通过 Session 配置

```php
use Fize\Session\Session;

new Session([
    'save_handler' => [
        'type'   => 'Redis',
        'config' => [
            'host'     => '127.0.0.1',
            'port'     => 6379,
            'timeout'  => 1.5,
            'password' => 'secret',
            'dbindex'  => 2,
            'expires'  => 3600,
        ],
    ],
]);

$_SESSION['uid'] = 1001;
```

### 手动注册

```php
use Fize\Session\Handler\RedisHandler;
use Fize\Session\Session;

$handler = new RedisHandler([
    'host'    => '127.0.0.1',
    'port'    => 6379,
    'expires' => 1800,
]);
Session::setSaveHandler($handler);
Session::start();
```

---

## 适用场景

- 多机共享会话，延迟要求低。
- 已有 Redis，希望用 TTL 自动淘汰会话。

不适合：

- 未安装 `ext-redis` 的环境（可用 [DatabaseHandler](DatabaseHandler.md) 或 [FileHandler](FileHandler.md)）。
- 需要在 `gc()` 中按 PHP `gc_maxlifetime` 扫描删除的场景（本类不会扫全库）。

---

## 注意事项

1. 构造即连接，失败会抛 `RuntimeException`。
2. 未设置 `expires` 时会话键不会自动过期，磁盘/内存会持续增长。
3. 无键前缀，避免与业务 Redis 键冲突。
4. 不要把本类与 `Session::moduleName('redis')` 混用在同一次请求里。
5. 单机单连接，没有官方集群 / Sentinel 配置项。

---

## 相关章节

- [Session](Session.md)
- [MemcachedHandler](MemcachedHandler.md)
- [DatabaseHandler](DatabaseHandler.md)
