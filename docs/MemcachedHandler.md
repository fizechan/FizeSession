# MemcachedHandler

- 命名空间：`Fize\Session\Handler`
- 源码：`src/Handler/MemcachedHandler.php`
- 会话配置 `type`：`Memcached`
- 依赖：`ext-memcached`（类 `Memcached`）
- 返回：[手册目录](index.md) · [Session](Session.md)

`MemcachedHandler` 使用 Memcached 协议存储会话，支持一次加入多台服务器。这是缓存型会话的推荐实现；旧的 [`MemcacheHandler`](MemcacheHandler.md) 已弃用。

连接在构造函数中通过 `addServers()` 完成。`open()` / `close()` / `gc()` 为空操作，过期依赖 `expires`。

---

## 类声明

```php
class MemcachedHandler extends SessionHandler implements SessionHandlerInterface
```

---

## 配置

```php
public function __construct(array $config = [])
```

默认值：

```php
[
    'servers' => [
        ['localhost', 11211, 0],
    ],
    'timeout' => 10,
    'expires' => 0,
]
```

| 键 | 类型 | 默认 | 说明 |
| --- | --- | --- | --- |
| `servers` | `array` | `[['localhost', 11211, 0]]` | 传给 `Memcached::addServers()` 的服务器列表 |
| `timeout` | `int` | `10` | **已写入配置但未被使用** |
| `expires` | `int` | `0` | `set()` 的过期时间。`0` 表示不过期；小于 30 天的正数视为相对秒数（Memcached 约定） |

`servers` 每一项为：

```text
[host, port, weight]
```

| 位置 | 含义 | 默认习惯 |
| --- | --- | --- |
| `0` | 主机 | 必填 |
| `1` | 端口 | `11211` |
| `2` | 权重 | `0` |

`addServers()` 失败时抛出 `RuntimeException`，消息与代码分别来自 `getResultMessage()`、`getResultCode()`。

与 [MemcacheHandler](MemcacheHandler.md) 的服务器元组不同：本类第三项是**权重**，旧扩展第三项是**是否长连接**。

---

## 键

Memcached 键为会话 ID，无前缀。会话 ID 通常满足 Memcached 键长度与字符限制，但不要自行使用含空格或超长 ID。

---

## 方法

### open / close

```php
public function open($path, $name): bool
public function close(): bool
```

忽略参数，不调用 `quit()`，均返回 `true`。

### read

```php
public function read($id): string
```

`get($id)`。若 `getResultCode() === Memcached::RES_NOTFOUND`，返回 `''`；否则返回取值。

### write

```php
public function write($id, $data): bool
```

```php
return $this->memcached->set($id, $data, $this->config['expires']);
```

返回 Memcached `set` 的布尔结果。

### destroy

```php
public function destroy($id): bool
```

`delete($id)`。若结果码为 `Memcached::RES_NOTFOUND`，视为成功并返回 `true`；否则返回 `delete` 的布尔值。

### gc

```php
public function gc($max_lifetime): bool
```

恒返回 `true`。过期由 Memcached 按 `expires` 处理。`expires` 为 `0` 时条目常驻，直到被 LRU 驱逐或手动 `destroy()`。

---

## 使用示例

### 单机

```php
use Fize\Session\Session;

new Session([
    'save_handler' => [
        'type'   => 'Memcached',
        'config' => [
            'servers' => [
                ['127.0.0.1', 11211, 0],
            ],
            'expires' => 3600,
        ],
    ],
]);
```

### 多节点

```php
use Fize\Session\Handler\MemcachedHandler;
use Fize\Session\Session;

$handler = new MemcachedHandler([
    'servers' => [
        ['10.0.0.11', 11211, 50],
        ['10.0.0.12', 11211, 50],
    ],
    'expires' => 1800,
]);
Session::setSaveHandler($handler);
Session::start();
```

---

## 适用场景

- 多机共享会话，已有 Memcached 集群。
- 希望用内存淘汰策略控制容量。

相对 [RedisHandler](RedisHandler.md)：

| 项目 | MemcachedHandler | RedisHandler |
| --- | --- | --- |
| 扩展 | `ext-memcached` | `ext-redis` |
| 多节点 | 构造时 `addServers` | 仅单机 host/port |
| 持久化 | 通常无 | 取决于 Redis 配置 |
| 默认过期 | `0`（不过期） | `null`（不过期） |

---

## 注意事项

1. 需要 `ext-memcached`，不是 `ext-memcache`。
2. `timeout` 配置项目前无效，超时走扩展/php.ini 默认值。
3. `expires = 0` 不会按 `gc_maxlifetime` 清理。
4. 无键前缀，注意与其他业务缓存隔离（可使用独立 Memcached 实例或不同服务器）。
5. 不要与 `Session::moduleName('memcached')` 混用。

---

## 相关章节

- [Session](Session.md)
- [MemcacheHandler](MemcacheHandler.md)（已弃用）
- [RedisHandler](RedisHandler.md)
