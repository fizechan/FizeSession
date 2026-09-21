# MemcacheHandler

- 命名空间：`Fize\Session\Handler`
- 源码：`src/Handler/MemcacheHandler.php`
- 会话配置 `type`：`Memcache`
- 依赖：`ext-memcache`（类 `Memcache`）
- 状态：**已弃用**（源码标注 `@deprecated 请使用 MemcachedHandler`）
- 返回：[手册目录](index.md) · [MemcachedHandler](MemcachedHandler.md) · [Session](Session.md)

`MemcacheHandler` 基于旧扩展 `ext-memcache`。新代码应使用 [`MemcachedHandler`](MemcachedHandler.md)。本节仅说明现存行为，便于维护旧项目。

---

## 类声明

```php
/**
 * @deprecated 请使用`MemcachedHandler`
 */
class MemcacheHandler extends SessionHandler implements SessionHandlerInterface
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
        ['localhost', 11211, true, 100],
    ],
    'expires' => null,
]
```

| 键 | 类型 | 默认 | 说明 |
| --- | --- | --- | --- |
| `servers` | `array` | 见上 | 逐台 `addServer()` |
| `expires` | `int\|null` | `null` | 传给 `Memcache::set()` 的过期参数 |

`servers` 每一项：

```text
[host, port = 11211, persistent = true, weight = 100]
```

| 位置 | 含义 | 缺省 |
| --- | --- | --- |
| `0` | 主机 | 必填 |
| `1` | 端口 | `11211` |
| `2` | 是否长连接 | `true` |
| `3` | 权重 | `100` |

任一台 `addServer()` 返回失败即抛出：

```text
RuntimeException: Error in addServer {host}.
```

注意：旧 `Memcache::addServer()` 在主机不可达时也常返回 `true`（真正失败发生在后续读写），因此构造成功不代表服务可用。

---

## 与 MemcachedHandler 的差异

| 项目 | MemcacheHandler | MemcachedHandler |
| --- | --- | --- |
| 扩展 | `ext-memcache` | `ext-memcached` |
| 加服务器 | 循环 `addServer` | 一次 `addServers` |
| 元组第三项 | 是否 persistent | weight |
| 元组第四项 | weight | 无 |
| `expires` 默认 | `null` | `0` |
| `read` 未命中 | `get === false` → `''` | `RES_NOTFOUND` → `''` |
| `destroy` 未命中 | 先 `get`，没有则直接成功 | 看 `RES_NOTFOUND` |
| 状态 | 已弃用 | 推荐 |

---

## 方法

### open / close

忽略参数，返回 `true`。不关闭连接。

### read

```php
public function read($id): string
```

`get($id)` 为 `false` 时返回 `''`。注意：`ext-memcache` 无法区分「不存在」和「存的就是 `false`」，会话数据一般是字符串，通常没有问题。

### write

```php
public function write($id, $data): bool
```

```php
return $this->memcache->set($id, $data, null, $this->config['expires']);
```

第三参数 flag 固定为 `null`，不使用 `MEMCACHE_COMPRESSED`。`$expires` 为 `null` 时行为取决于扩展，多数情况下接近“不设置过期”。

### destroy

```php
public function destroy($id): bool
```

先 `get($id)`，不存在则返回 `true`；存在再 `delete($id)`。

### gc

```php
public function gc($max_lifetime): bool
```

恒返回 `true`。过期依赖 Memcache 的 TTL / LRU。

---

## 使用示例（仅维护旧代码）

```php
use Fize\Session\Session;

new Session([
    'save_handler' => [
        'type'   => 'Memcache',
        'config' => [
            'servers' => [
                ['127.0.0.1', 11211, true, 100],
            ],
            'expires' => 3600,
        ],
    ],
]);
```

迁移时把 `type` 改为 `Memcached`，并改写 `servers` 元组（去掉 persistent，第三项改为 weight）。详见 [MemcachedHandler](MemcachedHandler.md)。

---

## 注意事项

1. 源码已标记弃用，不要在新项目中选用。
2. `ext-memcache` 在 PHP 7+ 上安装和维护都更困难。
3. 无键前缀；`gc()` 为空操作。
4. 不要与 `ext-memcached` 或 `Session::moduleName('memcache')` 混用。

---

## 相关章节

- [MemcachedHandler](MemcachedHandler.md)
- [Session](Session.md)
- [RedisHandler](RedisHandler.md)
