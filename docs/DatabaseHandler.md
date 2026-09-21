# DatabaseHandler

- 命名空间：`Fize\Session\Handler`
- 源码：`src/Handler/DatabaseHandler.php`
- 会话配置 `type`：`Database`
- 依赖：`fize/database`（`Fize\Database\Db`、`Fize\Database\Core\Db`）
- 返回：[手册目录](index.md) · [Session](Session.md)

`DatabaseHandler` 将会话行存入关系库。连接在 `open()` 时通过 `Db::connect()` 建立，`close()` 时丢弃。当前 [`init()`](#init) 只生成 MySQL 建表语句。

---

## 类声明

```php
class DatabaseHandler extends SessionHandler implements SessionHandlerInterface
```

---

## 配置

```php
public function __construct(array $config = [])
```

默认值：

```php
[
    'table' => 'session',
]
```

与传入数组合并后使用。

| 键 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| `table` | `string` | 否 | 表名，默认 `session` |
| `database` | `array` | 是 | `fize/database` 连接描述，在 `open()` / `init()` 中读取 |

`database` 结构：

| 键 | 类型 | 必填 | 说明 |
| --- | --- | --- | --- |
| `type` | `string` | 是 | 驱动类型，如 `mysql` |
| `config` | `array` | 是 | 传给 `Db::connect()` 的连接参数 |
| `mode` | `string\|null` | 否 | 连接模式，例如测试里使用的 `pdo` |

`config` 的常见 MySQL 字段（以本库测试为准）：

| 键 | 说明 |
| --- | --- |
| `host` | 主机 |
| `user` | 用户名 |
| `password` | 密码 |
| `dbname` | 数据库名 |

**配置键是 `database`，不是 `db`。** `demo/` 与部分示例写成了 `db`，与当前源码不符，按 `database` 编写才能连上。

```php
$handlerConfig = [
    'table'    => 'sys_session',
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
```

---

## 表结构

静态方法 [`init()`](#init) 在 MySQL 下执行的建表语句如下（表名来自 `$config['table']`）：

```sql
CREATE TABLE `{table}` (
  `id` varchar(100) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT 'ID',
  `data` blob NULL DEFAULT NULL COMMENT '数据',
  `atime` int(10) NOT NULL COMMENT '访问时间',
  `ctime` int(10) NOT NULL COMMENT '生成时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `idx_id`(`id`) USING BTREE,
  UNIQUE INDEX `idx_atime`(`atime`) USING BTREE,
  UNIQUE INDEX `idx_ctime`(`ctime`) USING BTREE
) ENGINE = MyISAM CHARACTER SET = utf8mb4 COMMENT = 'SESSION'
```

| 列 | 含义 | 谁维护 |
| --- | --- | --- |
| `id` | 会话 ID | 主键 |
| `data` | 会话编码二进制 | `write()` |
| `atime` | 最近访问 Unix 时间戳 | `read()`、`write()` |
| `ctime` | 生成时间戳 | `write()` 每次都会写入当前时间 |

使用注意：

1. `id` 已是主键，`idx_id` 多余。
2. `atime`、`ctime` 上的 **UNIQUE** 在同一秒内出现两条会话时可能冲突。自行建表时建议改为普通索引。
3. `write()` 在更新已存在行时仍会刷新 `ctime`，因此 `ctime` 实际更接近“最近写入时间”，与注释“生成时间”不完全一致。
4. `data` 使用 `blob`，可容纳 PHP 会话编码后的二进制。

非 MySQL 库需要自行建兼容表，列名至少包含 `id`、`data`、`atime`、`ctime`。

---

## 方法

### open

```php
public function open($path, $name): bool
```

忽略 `$path`、`$name`。读取 `$this->config['database']`，调用：

```php
$this->db = Db::connect($dbcfg['type'], $dbcfg['config'], $mode);
```

连接成功则返回 `true`。`$this->config['database']` 缺失时会在运行期报错。

### close

```php
public function close(): bool
```

将 `$this->db` 置 `null`，返回 `true`。不显式断开底层连接，依赖 `fize/database` 对象析构。

### read

```php
public function read($id): string
```

按主键查一行：

- 无记录：返回 `''`。
- 有记录：把 `atime` 更新为 `time()`，返回 `data` 字段。

读操作会刷新访问时间，从而推迟 GC。

### write

```php
public function write($id, $data): bool
```

组装行：

```php
[
    'id'    => $id,
    'data'  => $data,
    'atime' => time(),
    'ctime' => time(),
]
```

已存在则 `update`，否则 `insert`。恒返回 `true`。

### destroy

```php
public function destroy($id): bool
```

按 `id` 删除行，恒返回 `true`。

### gc

```php
public function gc($max_lifetime): bool
```

删除同时满足以下条件的行：

```text
atime < now - max_lifetime
ctime < now - max_lifetime
```

源码使用的是 `fize/database` 的数组条件写法。恒返回 `true`。方法带有 `#[\ReturnTypeWillChange]`，以兼容不同 PHP 版本对 `gc()` 返回类型的差异。

与 [FileHandler](FileHandler.md) 一样，判定是「atime 与 ctime 都过期」。因为 `write()` 会同时刷新两个时间戳，正常写回的会话两者接近；若只被 `read()` 刷新了 `atime`，`ctime` 仍偏旧时，要等 `atime` 也过期才会删。

---

### init

```php
public static function init(array $config)
```

按配置建表。与实例方法不同，这里**不会**默认补 `table`，调用方必须提供 `table` 与 `database`。

```php
use Fize\Session\Handler\DatabaseHandler;

DatabaseHandler::init([
    'table'    => 'sys_session',
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
]);
```

`database.type` 会转成小写后匹配。目前仅 `mysql`；其他类型抛出：

```text
RuntimeException: 暂不支持{type}数据库驱动
```

建表使用 `CREATE TABLE`，表已存在时由数据库报错。该方法只负责建表，不启动会话。

对应测试：`tests/Handler/TestDatabaseHandler.php`。

---

## 使用示例

### 先建表，再通过 Session 启动

```php
use Fize\Session\Handler\DatabaseHandler;
use Fize\Session\Session;

$config = [
    'table'    => 'sys_session',
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

DatabaseHandler::init($config);

new Session([
    'save_handler' => [
        'type'              => 'Database',
        'config'            => $config,
        'register_shutdown' => true,
    ],
]);

$_SESSION['admin'] = [
    'name' => 'Fize',
    'age'  => 30,
];
```

### 手动注册

```php
use Fize\Session\Handler\DatabaseHandler;
use Fize\Session\Session;

$handler = new DatabaseHandler($config);
Session::setSaveHandler($handler);
Session::start();
```

---

## 适用场景

- 多机部署、需要共享会话，且已有 MySQL（或兼容表结构的其他库）。
- 需要用 SQL 排查、统计在线会话。

不适合：

- 超高 QPS 的会话读写（每请求至少一次读 + 一次写，读还会多一次更新 `atime`）。
- 尚未引入 `fize/database` 的项目（可改用 [RedisHandler](RedisHandler.md)）。

---

## 注意事项

1. 必须能加载 `fize/database`，连接参数需符合该库当前版本。
2. `open()` 每次会话启动都 `Db::connect()`，依赖连接库自身的复用策略。
3. `demo/Handler/DatabaseHandler/` 与 `demo/__construct.php`、`demo/setSaveHandler.php` 使用了错误键名 `db`，请以本文的 `database` 为准。
4. `init()` 生成的 UNIQUE 时间索引在生产环境建议评估后改为非唯一索引。
5. 表名会直接拼进 SQL，不要传入未信任的表名。

---

## 相关章节

- [Session](Session.md)
- [FileHandler](FileHandler.md)
- [RedisHandler](RedisHandler.md)
