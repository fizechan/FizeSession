# FizeSession 参考手册

FizeSession 是一个面向 PHP 的 Session 底层管理类库。它把 PHP 原生 `session_*` 函数收拢到 [`Session`](Session.md) 类中，并提供可替换的自定义存储处理器，将会话数据放到文件、数据库、Redis 或 Memcached。

- 包名：`fize/session`
- 命名空间：`Fize\Session`
- 许可：MIT
- 仓库：[github.com/fizechan/FizeSession](https://github.com/fizechan/FizeSession)
- 在线手册：[fizesession.readthedocs.io](https://fizesession.readthedocs.io/zh_CN/latest/index.html)

本文档依据当前源码整理，作为本地参考手册使用。

---

## 目录

| 章节 | 说明 |
| --- | --- |
| [Session](Session.md) | 会话门面类：初始化、生命周期、Cookie、编解码、垃圾回收 |
| [FileHandler](FileHandler.md) | 文件处理器（依赖 `fize/io`） |
| [DatabaseHandler](DatabaseHandler.md) | 数据库处理器（依赖 `fize/database`，当前建表仅支持 MySQL） |
| [RedisHandler](RedisHandler.md) | Redis 处理器（依赖 `ext-redis`） |
| [MemcachedHandler](MemcachedHandler.md) | Memcached 处理器（依赖 `ext-memcached`，推荐） |
| [MemcacheHandler](MemcacheHandler.md) | Memcache 处理器（依赖 `ext-memcache`，已弃用） |
| [MockHandler](MockHandler.md) | 进程内模拟处理器，主要用于测试 |

---

## 特性

- **薄封装**：`Session` 的公开方法基本一一对应 PHP 原生会话函数，调用习惯与官方 API 接近。
- **可替换存储**：通过 `save_handler.type` 或 `Session::setSaveHandler()` 切换存储后端。
- **构造即初始化**：`new Session($config)` 会按配置写入缓存策略、Cookie、保存路径，并在会话未启动时自动 `session_start()`。
- **标准处理器接口**：所有内置 Handler 都继承 `SessionHandler` 并实现 `SessionHandlerInterface`，可按同样契约自行扩展。

---

## 环境要求

| 项目 | 要求 |
| --- | --- |
| PHP | `>= 7.1.0`（`composer.json` 建议 `>= 7.2.0`） |
| 默认存储 | 无需额外扩展，使用 PHP 原生 `files` 处理器即可 |
| 文件处理器 | 建议安装 `fize/io` |
| 数据库处理器 | 建议安装 `fize/database`，并具备对应数据库驱动 |
| Redis 处理器 | `ext-redis` |
| Memcached 处理器 | `ext-memcached` |
| Memcache 处理器 | `ext-memcache`（已弃用，请改用 Memcached） |

Composer 的 `suggest` 与上述可选依赖一致，按实际选用的 Handler 安装即可。

---

## 安装

```bash
composer require fize/session
```

按需补充依赖：

```bash
composer require fize/io          # FileHandler
composer require fize/database    # DatabaseHandler
```

Redis / Memcached / Memcache 通过 PHP 扩展提供，不经过 Composer。

---

## 源码结构

```text
FizeSession/
├── src/
│   ├── Session.php                    # Fize\Session\Session
│   └── Handler/
│       ├── FileHandler.php            # 文件
│       ├── DatabaseHandler.php        # 数据库
│       ├── RedisHandler.php           # Redis
│       ├── MemcachedHandler.php       # Memcached
│       ├── MemcacheHandler.php        # Memcache（已弃用）
│       └── MockHandler.php            # 测试用模拟
├── demo/                              # 用法示例（打包时 export-ignore）
├── tests/                             # PHPUnit（覆盖面较窄）
├── docs/                              # 本参考手册
├── composer.json
└── README.md
```

自动加载规则：

```text
Fize\Session\          →  src/
Fize\Session\Handler\  →  src/Handler/
```

---

## 架构

```text
业务代码
   │
   │  new Session($config)  或  Session::start()
   ▼
┌──────────────────────────────────────────┐
│           Fize\Session\Session           │
│  配置合并 / 注册 Handler / 启动会话       │
│  静态方法代理 session_* 函数              │
└──────────────────────────────────────────┘
   │
   │  session_set_save_handler()
   ▼
┌──────────────────────────────────────────┐
│     SessionHandlerInterface 六方法        │
│  open / close / read / write / destroy / gc │
└──────────────────────────────────────────┘
   │
   ├── type 为空或 files → PHP 原生文件存储
   ├── File        → FileHandler
   ├── Database    → DatabaseHandler
   ├── Redis       → RedisHandler
   ├── Memcached   → MemcachedHandler
   ├── Memcache    → MemcacheHandler
   └── Mock        → MockHandler
```

处理器类名由配置拼出：

```text
\Fize\Session\Handler\{type}Handler
```

因此 `type` 必须与类名前缀一致，例如 `Database`、`File`、`Redis`、`Memcached`、`Memcache`、`Mock`。`files`（全小写、复数）是特例，表示不加载本库 Handler，继续使用 PHP 内置文件存储。

---

## 两种使用方式

### 1. 构造函数一次性初始化（推荐）

适合在应用入口完成全部会话配置：

```php
use Fize\Session\Session;

new Session([
    'name'          => 'PHPSESSID',
    'save_path'     => '/tmp/sessions',
    'cache_expire'  => 180,
    'cache_limiter' => 'nocache',
    'options'       => [
        'gc_maxlifetime' => 1440,
    ],
    'save_handler'  => [
        'type'              => '',   // 空或 files：原生文件存储
        'config'            => [],
        'register_shutdown' => true,
    ],
]);

$_SESSION['uid'] = 1;
```

构造完成后，若当前状态为 `PHP_SESSION_NONE`，会立刻启动会话。之后直接读写 `$_SESSION` 即可。

完整配置项见 [Session · 配置](Session.md#配置)。

### 2. 手动注册处理器并启动

适合需要自行控制启动时机，或使用本库以外的 `SessionHandler` 实现：

```php
use Fize\Session\Handler\FileHandler;
use Fize\Session\Session;

$handler = new FileHandler();
Session::setSaveHandler($handler);
Session::savePath(sys_get_temp_dir() . '/sessions');
Session::start();

$_SESSION['uid'] = 1;
Session::writeClose();
```

---

## 快速开始

### 使用 PHP 原生文件存储

```php
use Fize\Session\Session;

new Session([
    'save_path' => sys_get_temp_dir(),
]);

$_SESSION['user'] = ['name' => 'Fize'];
```

### 使用 Redis

```php
use Fize\Session\Session;

new Session([
    'save_handler' => [
        'type'   => 'Redis',
        'config' => [
            'host'    => '127.0.0.1',
            'port'    => 6379,
            'expires' => 3600,
        ],
    ],
]);
```

### 使用数据库

先建表（仅 MySQL 可由库生成），再启动：

```php
use Fize\Session\Handler\DatabaseHandler;
use Fize\Session\Session;

$handlerConfig = [
    'table'    => 'session',
    'database' => [
        'type'   => 'mysql',
        'mode'   => 'pdo',
        'config' => [
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => '',
            'dbname'   => 'app',
        ],
    ],
];

DatabaseHandler::init($handlerConfig);

new Session([
    'save_handler' => [
        'type'   => 'Database',
        'config' => $handlerConfig,
    ],
]);
```

表结构与字段含义见 [DatabaseHandler · 表结构](DatabaseHandler.md#表结构)。

---

## 会话生命周期

| 阶段 | 典型调用 | 作用 |
| --- | --- | --- |
| 配置 | `new Session($config)` 或各静态 setter | 必须在会话启动前完成 |
| 启动 | `Session::start()`（构造函数会自动调用） | 打开存储、读取并解码到 `$_SESSION` |
| 读写 | `$_SESSION[$key]` | 业务数据始终走超全局变量 |
| 丢弃本次更改 | [`Session::abort()`](Session.md#abort) | 不写回存储，结束会话 |
| 恢复启动时快照 | [`Session::reset()`](Session.md#reset) | 丢弃当前对 `$_SESSION` 的修改 |
| 清空变量 | [`Session::unset()`](Session.md#unset) | 清空 `$_SESSION`，存储记录仍在 |
| 销毁存储 | [`Session::destroy()`](Session.md#destroy) | 删除存储中的当前会话 |
| 正常结束 | [`Session::writeClose()`](Session.md#writeclose) | 编码写回并关闭；请求结束时也会自动发生 |
| 垃圾回收 | [`Session::gc()`](Session.md#gc) | 清理过期会话；缓存型后端多为空操作 |

---

## 自定义处理器

将类放到 `Fize\Session\Handler` 下并命名为 `{Type}Handler` 后，即可用 `type = '{Type}'` 加载；也可以实现 `SessionHandlerInterface` 后手动注册：

```php
use SessionHandler;
use SessionHandlerInterface;
use Fize\Session\Session;

class CustomHandler extends SessionHandler implements SessionHandlerInterface
{
    public function open($path, $name): bool { return true; }
    public function close(): bool { return true; }
    public function read($id): string { return ''; }
    public function write($id, $data): bool { return true; }
    public function destroy($id): bool { return true; }
    public function gc($max_lifetime): bool { return true; }
}

Session::setSaveHandler(new CustomHandler());
Session::start();
```

`read()` 必须返回会话编码字符串（找不到记录时返回空字符串，不要返回 `false`，以免 PHP 7.1+ 发出警告）。`write()` 收到的 `$data` 已是 PHP 会话序列化结果，处理器不应再按业务数组理解它。

---

## 使用注意

1. **输出前启动会话**。Cookie 头必须在任何输出之前发送。
2. **`Session::id()` 只能读取**，不能设置会话 ID。如需轮换 ID，使用 [`regenerateId()`](Session.md#regenerateid)。
3. **`cookie_params` 配置当前不会生效**。构造函数判断了未定义的 `$cookie_params` 变量，启动前请显式调用 [`setCookieParams()`](Session.md#setcookieparams)。详见 [Session · 已知实现问题](Session.md#已知实现问题)。
4. **`demo/` 中数据库配置键名与源码不一致**。示例多用 `db`，而 [`DatabaseHandler`](DatabaseHandler.md) 实际读取的是 `database`。
5. **Redis / Memcached / Memcache 的 `gc()` 为空操作**，过期依赖各自的 TTL。
6. **`MemcacheHandler` 已标记 `@deprecated`**，新代码请使用 [`MemcachedHandler`](MemcachedHandler.md)。
7. **单元测试覆盖很少**。`tests/TestSession.php` 几乎未断言行为；`MockHandler` 也不适合作为生产存储。

---

## 许可协议

本项目使用 [MIT License](../LICENSE)。版权所有 © 2019 FizeChan。
