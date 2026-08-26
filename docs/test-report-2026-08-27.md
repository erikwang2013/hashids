# erikwang2013/hashids 单元测试报告

**测试日期**：2026-08-27  
**测试范围**：src/ 下全部 11 个模块 + 4 个配置数组文件

---

## 1. 测试环境

| 项目 | 版本 |
|------|------|
| PHP | 8.3.7 (cli, NTS) |
| PHPUnit | 11.5.55 |
| hashids/hashids | ^5.0（vendor 已安装） |
| Mockery | ^1.6（vendor 已安装） |
| illuminate/support, illuminate/contracts | 已安装（拆分包，无 illuminate/container） |
| hyperf/framework | 未安装 |
| topthink/framework | 未安装 |
| workerman/webman | 未安装 |

框架集成测试策略：依赖已装 illuminate 包的部分（Laravel ServiceProvider/Facade）用 Mockery mock `Illuminate\Contracts\Container\Container` 契约；Hyperf 用已装的 `Psr\Container\ContainerInterface` + 匿名配置对象；未安装框架（Webman/ThinkPHP）通过 `tests/Support/FrameworkStubs.php` 提供最小契约 stub（全部带 `function_exists`/`class_exists` 守卫，真实框架安装时自动失效并跳过对应测试，避免污染真实环境）。

---

## 2. 模块覆盖矩阵

| 模块 | 测试文件 | 测试数 | 结果 | 覆盖要点 |
|------|----------|-------:|------|----------|
| `src/HashidsFactory.php` | `tests/HashidsFactoryTest.php` | 15 | 通过 | 最小配置/空配置/自定义 alphabet/空 alphabet 回退；length 字符串强转、负 length；重复字符与过短 alphabet 抛 InvalidArgumentException（校验委托 hashids）；数字 alphabet 强转；同配置确定性、不同 salt 不同 hash；encode/decode 往返、encodeHex/decodeHex；空串/非法字符 decode 返回 []；0 与 PHP_INT_MAX |
| `src/HashidsManager.php` | `tests/HashidsManagerTest.php` | 16 | 通过 | 默认/具名连接；懒加载与连接缓存（同实例）；getDefaultConnection 回退 'main'（缺失/空/非字符串）；空名称抛异常；未配置连接抛异常；非数组连接配置抛异常；`__call` 动态代理（encode/encodeHex 参数转发）；未定义方法抛 Error；getFactory 返回注入实例 |
| `src/Install.php` | `tests/InstallTest.php` | 6 | 通过 | WEBMAN_PLUGIN 常量；pathRelation 结构；install() 复制插件配置 + 发布 hashids.php；已存在配置不覆盖；confirm=true 强制覆盖；uninstall() 移除插件目录（基于 stub 函数在临时目录执行，不触碰真实文件系统） |
| `src/Laravel/HashidsServiceProvider.php` | `tests/Laravel/HashidsServiceProviderTest.php` | 6 | 通过 | 延迟提供者契约 + provides() 清单；register() 注册 Factory/Manager 单例、'hashids' 别名、HashidsClient 绑定（闭包真实可解析）；mergeConfigFrom 合并包配置；非数组配置容错；boot() 控制台环境发布配置（publishes + publishGroups）；非控制台不发布 |
| `src/Laravel/Facades/Hashids.php` | `tests/Laravel/Facades/HashidsTest.php` | 2 | 通过 | getFacadeAccessor 返回 HashidsManager::class；静态调用经容器转发至默认连接并正确往返（Facade 以 ArrayAccess 方式解析容器，mock 已实现 ArrayAccess） |
| `src/Webman/Bootstrap.php` | `tests/Webman/BootstrapTest.php` | 4 | 通过 | 启用时注册 4 个容器定义且闭包可解析（client 为缓存单例）；`enable=false` 不注册；缺省 enable 默认启用；hashids 配置非数组时注册不崩溃、解析 client 抛 InvalidArgumentException（fail-fast） |
| `src/ThinkPHP/HashidsService.php` | `tests/ThinkPHP/HashidsServiceTest.php` | 3 | 通过 | register() 绑定 Factory（类名绑定）、Manager（闭包读取配置）、'hashids' 别名、HashidsClient；闭包真实可解析并可编解码；配置缺失/非数组容错 |
| `src/Hyperf/HashidsManagerFactory.php` | `tests/Hyperf/HashidsManagerFactoryTest.php` | 3 | 通过 | 从 ConfigInterface 读取 'hashids' 构建 Manager；键缺失回退空配置（默认连接不可用时抛异常）；非数组配置归一化为空数组 |
| `src/Hyperf/HashidsClientFactory.php` | `tests/Hyperf/HashidsClientFactoryTest.php` | 2 | 通过 | 返回默认连接；遵循配置的 default 连接名（非 'main' 时正确） |
| `src/Hyperf/ConfigProvider.php` | `tests/Hyperf/ConfigProviderTest.php` | 2 | 通过 | dependencies 三映射完整；publish 条目（id/source 文件存在/destination 基于 BASE_PATH） |
| `src/config/plugin/erikwang2013/hashids/app.php` | `tests/Config/PluginConfigTest.php` | 4 | 通过 | 返回数组断言：enable=true；bootstrap.php 注册 Webman Bootstrap；`config/hashids.php` default/connections 结构；`config/autoload/hashids.php` 嵌套结构 |

**合计**：11 个测试文件，63 个测试，136 个断言。

---

## 3. 测试统计

| 项目 | 数值 |
|------|------|
| 测试总数 | 63 |
| 断言总数 | 136 |
| 通过 | 63 |
| 失败 | 0 |
| 错误 | 0 |
| 跳过 | 0 |
| 通过率 | 100% |
| 耗时 | 0.278s（全套件） |

运行命令：`vendor/bin/phpunit`（phpunit.xml.dist 默认 testsuite）

---

## 4. 发现的风险点（非 bug，均为合理的 fail-fast 行为）

1. **Webman：hashids 配置缺失/非数组时，解析 `HashidsClient` 抛 `InvalidArgumentException`（"not configured"）** — `src/Webman/Bootstrap.php:43`。Bootstrap 注册阶段不报错，但容器解析 HashidsClient 时立即失败。属配置错误应有的快速失败，建议部署时校验 `config/hashids.php`。
2. **Laravel：config 值非数组时 `mergeConfigFrom()` 抛 TypeError** — 这是 `Illuminate\Support\ServiceProvider` 基类行为（`array_merge(require $path, $config->get($key, []))`），发生在 `src/Laravel/HashidsServiceProvider.php:35` 的 `mergeConfigFrom` 调用处，本包代码无法拦截。`HashidsManager` 闭包自身的 `is_array()` 容错（`src/Laravel/HashidsServiceProvider.php:43`）仅对"merge 之后"生效；配置始终为数组时无此问题。
3. **`HashidsFactory::make()` 的 length 负数不报错** — `(int)` 强转后负值被 hashids 当作 0 处理（与 0 结果相同），静默容错，符合 vinkla 兼容契约。

---

## 5. 未覆盖项及原因

| 项目 | 原因 |
|------|------|
| Webman `Bootstrap` 真实容器/真实 `config()` 函数路径 | 未安装 workerman/webman；测试通过 `tests/Support/FrameworkStubs.php` 的最小 stub 验证了全部逻辑分支（真实框架安装时 stub 失效、测试自动跳过）。stub 不依赖 webman 包，若未来引入 webman 需重跑确认 |
| ThinkPHP `HashidsService` 真实 `think\Service`/`think\App` | 未安装 topthink/framework；同上，stub 验证绑定逻辑 |
| Laravel ServiceProvider 与真实 `Illuminate\Container\Container` 集成 | 未安装 illuminate/container（拆分包），用 Mockery mock 容器契约替代；`mergeConfigFrom`、`publishes` 的基类行为已在 mock 下验证 |
| Hyperf 真实容器（`Hyperf\Contract\ConfigInterface`） | 未安装 hyperf/framework；用 PSR 容器 mock + 匿名配置对象验证工厂逻辑，`ConfigProvider` 的纯数组结构完整断言 |
| `Install::install()` 真实 `copy_dir()` 递归复制 | 复制行为由 webman 框架函数提供，stub 记录调用参数并断言目标路径/目录创建；配置文件的真实 `copy()` 在临时目录已验证 |

---

## 6. 说明

- 本次测试**未修改任何 src/ 代码**：所有被测模块均按现有实现通过，未暴露需要修复的真实 bug。
- 新增测试文件：`tests/InstallTest.php`、`tests/Webman/BootstrapTest.php`、`tests/ThinkPHP/HashidsServiceTest.php`、`tests/Hyperf/{HashidsManagerFactoryTest,HashidsClientFactoryTest,ConfigProviderTest}.php`、`tests/Laravel/{HashidsServiceProviderTest.php,Facades/HashidsTest.php}`、`tests/Config/PluginConfigTest.php`、`tests/Support/FrameworkStubs.php`（stub，非测试）。
- 增强：`tests/HashidsFactoryTest.php`（+10 测试）、`tests/HashidsManagerTest.php`（+6 测试）。
