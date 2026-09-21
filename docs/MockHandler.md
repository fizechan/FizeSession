# MockHandler

- 命名空间：`Fize\Session\Handler`
- 源码：`src/Handler/MockHandler.php`
- 会话配置 `type`：`Mock`
- 依赖：无第三方库
- 返回：[手册目录](index.md) · [Session](Session.md)

`MockHandler` 把会话编码字符串存在进程内的 `$_SESSION[$id]`。源码说明其用途是「在 WEB 单元测试时很有用」。

它**不是**生产存储。实现会直接改写超全局变量 `$_SESSION`，与 PHP 会话引擎自己对 `$_SESSION` 的使用重叠，行为容易互相覆盖。

---

## 类声明

```php
class MockHandler extends SessionHandler implements SessionHandlerInterface
```

构造函数无参数：

```php
public function __construct()
{
    global $_SESSION;
    $_SESSION = [];
}
```

实例化当下就会把 `$_SESSION` 重置为空数组。

---

## 配置

没有配置项。若通过 `Session` 传入 `save_handler.config`，该数组会被丢弃（构造函数不接收参数；`new $class($cfg_handler['config'])` 在 PHP 中对无参构造仍可传入多余参数，但本类不会使用）。

```php
new Session([
    'save_handler' => [
        'type'   => 'Mock',
        'config' => [],
    ],
]);
```

---

## 存储模型

| 操作 | 实际效果 |
| --- | --- |
| 构造 / `open()` | `$_SESSION = []` |
| `read($id)` | 返回 `$_SESSION[$id] ?? ''` |
| `write($id, $data)` | `$_SESSION[$id] = $data` |
| `destroy($id)` | `unset($_SESSION[$id])` |
| `close()` / `gc()` | 空操作，返回 `true` |

这里的 `$data` 是会话编码字符串，因此 `$_SESSION` 在 Handler 视角下是「ID → 编码串」映射；而 PHP 在 `read()` 之后会把解码结果写进同一个 `$_SESSION`，变成「业务键 → 业务值」。

一次正常 `session_start()` 的粗略顺序：

1. `open()` 清空 `$_SESSION`。
2. `read($id)` 此时几乎总是得到 `''`（刚被清空）。
3. 引擎把解码结果写入 `$_SESSION`（业务数据）。
4. 请求结束 `write($id, $encoded)`，在业务数组上多出一个键 `$id`，值为编码串。

因此：

- 无法在两次 `session_start()` 之间凭本 Handler 恢复上次业务数据（`open()` 已清空）。
- `$_SESSION` 会被混入会话 ID 键，可能污染业务读取。
- 跨请求、跨进程都不共享。

它更适合**单独测 Handler 六个方法**，而不是作为 `Session::start()` 的后端做集成测试。当前 `tests/` 也没有覆盖本类。

---

## 方法

### open

```php
public function open($path, $name): bool
```

再次将 `$_SESSION` 置为 `[]`，返回 `true`。忽略 `$path`、`$name`。

### close

```php
public function close(): bool
```

返回 `true`。

### read

```php
public function read($id): string
```

返回 `$_SESSION[$id] ?? ''`。

### write

```php
public function write($id, $data): bool
```

`$_SESSION[$id] = $data`，返回 `true`。

### destroy

```php
public function destroy($id): bool
```

`unset($_SESSION[$id])`，返回 `true`。

### gc

```php
public function gc($max_lifetime): bool
```

不做清理，返回 `true`。内存数组没有过期概念。

---

## 使用示例

仅演示直接调用 Handler，避免与引擎抢 `$_SESSION`：

```php
use Fize\Session\Handler\MockHandler;

$handler = new MockHandler();
$handler->open('', 'PHPSESSID');

$id = 'test-session-id';
$handler->write($id, 'admin|a:1:{s:4:"name";s:4:"Fize";}');
echo $handler->read($id);

$handler->destroy($id);
$handler->close();
```

若在 PHPUnit 里只想绕过“必须有已启动的 Web 会话”，更稳妥的做法往往是：

- CLI 下用 `session_start()` + 原生 `files` 指向临时目录；或
- 自己提供一个把数据放在普通数组属性（而不是 `$_SESSION`）里的 Handler。

---

## 注意事项

1. 不要在生产环境配置 `type => 'Mock'`。
2. 构造和 `open()` 会清空整个 `$_SESSION`。
3. 无持久化、无 TTL、无并发隔离。
4. 源码注释中的“WEB 单元测试”场景，当前仓库几乎没有对应测试用例。

---

## 相关章节

- [Session](Session.md)
- [FileHandler](FileHandler.md)
