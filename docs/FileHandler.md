# FileHandler

- 命名空间：`Fize\Session\Handler`
- 源码：`src/Handler/FileHandler.php`
- 会话配置 `type`：`File`
- 依赖：`fize/io`（`Fize\IO\File`、`Fize\IO\Directory`）
- 返回：[手册目录](index.md) · [Session](Session.md)

`FileHandler` 把每个会话存成保存目录下的一个文件，文件名即会话 ID，文件内容为 PHP 会话编码字符串。

它与 PHP 原生 `files` 处理器目标类似，但是本库自己的实现，走 `session_set_save_handler()`，模块名一般为 `user`。若只需要 PHP 内置文件存储，把 `save_handler.type` 留空或设为 `files` 即可，不必使用本类。

---

## 类声明

```php
class FileHandler extends SessionHandler implements SessionHandlerInterface
```

---

## 配置

构造函数接收 `$config` 并保存在私有属性中，**当前实现不会读取任何配置键**。保存目录完全来自 PHP 在 `open()` 时传入的 `$path`，也就是 [`Session::savePath()`](Session.md#savepath) 或 `session.save_path`。

```php
public function __construct(array $config = [])
```

仍建议在 `Session` 配置里显式传空数组，避免与其他 Handler 的写法不一致：

```php
new Session([
    'save_path'    => '/var/lib/php/app_sessions',
    'save_handler' => [
        'type'   => 'File',
        'config' => [],
    ],
]);
```

---

## 存储布局

假设 `save_path` 为 `/var/lib/php/app_sessions`，会话 ID 为 `abc123`：

```text
/var/lib/php/app_sessions/abc123
```

- 文件名：**不做** `sess_` 前缀（PHP 原生 `files` 默认常为 `sess_{id}`）。
- 内容：`session_encode()` 风格的原始字符串，不再二次包装。
- 目录：`open()` 只记录路径，**不会创建目录**。使用前需保证目录存在且 PHP 进程可写。

---

## 方法

与 `SessionHandlerInterface` 一致，均由 PHP 会话引擎在适当时机回调，业务代码通常不必直接调用。

### open

```php
public function open($path, $name): bool
```

记录 `$path` 为内部 `$savePath`，忽略 `$name`，恒返回 `true`。

### close

```php
public function close(): bool
```

无额外资源需要释放，恒返回 `true`。

### read

```php
public function read($id): string
```

读取 `$savePath/$id`。文件不存在时返回空字符串 `''`（符合 PHP 7.1+ 对 Handler 的要求）。存在则 `File::getContents()` 返回全部内容。

### write

```php
public function write($id, $data): bool
```

以 `w+` 打开 `$savePath/$id` 并写入 `$data`。当前实现不检查 `fwrite` 是否成功，恒返回 `true`。

### destroy

```php
public function destroy($id): bool
```

删除 `$savePath/$id`，恒返回 `true`。对应 [`Session::destroy()`](Session.md#destroy)。

### gc

```php
public function gc($max_lifetime): bool
```

扫描保存目录（不递归进入子目录）：

1. 跳过子目录项。
2. 对文件同时读取访问时间 `atime` 与创建/inode 变更时间 `ctime`。
3. 仅当 `(now - atime) > max_lifetime` **并且** `(now - ctime) > max_lifetime` 时删除。

恒返回 `true`。条件是「与」关系，只过期访问时间或只过期创建时间都不会删。部分文件系统或挂载选项会关闭 `atime` 更新，可能导致文件长期不被回收。

---

## 使用示例

### 通过 Session 配置加载

```php
use Fize\Session\Session;

$savePath = __DIR__ . '/runtime/sessions';
if (!is_dir($savePath)) {
    mkdir($savePath, 0770, true);
}

new Session([
    'save_path'    => $savePath,
    'save_handler' => [
        'type'   => 'File',
        'config' => [],
    ],
    'options' => [
        'gc_maxlifetime' => 1440,
    ],
]);

$_SESSION['admin'] = [
    'name' => 'Fize',
    'time' => date('Y-m-d H:i:s'),
];
```

### 手动注册

```php
use Fize\Session\Handler\FileHandler;
use Fize\Session\Session;

$handler = new FileHandler();
Session::setSaveHandler($handler);
Session::savePath(__DIR__ . '/runtime/sessions');
Session::start();
```

对应 demo：`demo/Handler/FileHandler/*.php`。

---

## 与原生 files 的差异

| 项目 | PHP 原生 `files` | FileHandler |
| --- | --- | --- |
| `type` | `''` 或 `files` | `File` |
| 文件名 | 多为 `sess_{id}` | `{id}` |
| 实现 | PHP 内核 | `fize/io` |
| 目录创建 | 视环境而定 | 不创建 |
| GC 判定 | 通常按 `mtime` | `atime` 与 `ctime` 同时超期 |
| 并发锁 | 内核文件锁 | 本类未额外加锁 |

多进程同时写同一会话时，本实现没有显式锁，可能出现互相覆盖。并发高的场景更适合 [RedisHandler](RedisHandler.md) 或 [MemcachedHandler](MemcachedHandler.md)。

---

## 注意事项

1. 必须先安装 `fize/io`，否则无法自动加载 `Fize\IO\File` / `Directory`。
2. `save_path` 必须在 [`Session::start()`](Session.md#start) 之前生效。
3. 不要把保存目录放到 Web 可直接访问的路径下。
4. 构造参数 `$config` 目前是预留位，写入后无效果。

---

## 相关章节

- [Session](Session.md)
- [DatabaseHandler](DatabaseHandler.md)
- [MockHandler](MockHandler.md)
