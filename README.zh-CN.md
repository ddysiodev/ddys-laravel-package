# 低端影视 API Laravel 扩展包

[English](README.md) | 中文

[低端影视](https://ddys.io/) API 的官方 Laravel 扩展包。它为 Laravel 应用提供前台页面、Blade 组件、Blade 指令、文本短代码、本地 JSON 代理、缓存、诊断、Artisan 命令和服务端求片表单。

- GitHub 仓库：[ddysiodev/ddys-laravel-package](https://github.com/ddysiodev/ddys-laravel-package)
- Composer 包名：`ddysiodev/ddys-laravel-package`
- 命名空间：`Ddys\Laravel`
- 目标环境：Laravel 10 / 11 / 12 / 13，PHP 8.1+
- 许可证：MIT

## 功能

- 通过 `extra.laravel.providers` 支持 Laravel 包自动发现。
- `DdysServiceProvider` 包含配置合并、路由、视图、Blade 指令、Blade 组件命名空间、发布分组和 Artisan 命令。
- 完整 DDYS API 客户端，覆盖影视、最新、热门、搜索、推荐、日历、影片详情、播放源、相关、评论、片单、分享、求片、动态、用户、字典、鉴权求片、评论、举报、关注和账号信息。
- `/ddys` 前缀下提供 20 个前台页面。
- `/ddys-api/{route}` 本地 JSON 代理，带路由和参数白名单。
- Blade 用法：`@ddys('latest', ['limit' => 12])`、`@ddysLatest(['limit' => 12])`、`<x-ddys::widget view="latest" :params="['limit' => 12]" />`。
- 解析全部 21 个 `[ddys_*]` 文本短代码。
- 服务端求片表单使用 Laravel CSRF、Validator 校验、蜜罐字段、IP 限流，并且 API Key 只在服务端使用。
- 缓存按接口类型分 TTL；驱动支持 tag 时使用 tag 清理，不支持时回退到 key 索引清理。
- 可发布配置、Blade 视图、CSS、JavaScript 和图标。
- 提供安装、诊断、路由查看、缓存清理 Artisan 命令。

## 安装

```bash
composer require ddysiodev/ddys-laravel-package
```

Packagist 可用前，可从 GitHub VCS 仓库安装：

```bash
composer config repositories.ddys-laravel-package vcs https://github.com/ddysiodev/ddys-laravel-package
composer require ddysiodev/ddys-laravel-package:^0.1
```

发布配置和资源：

```bash
php artisan ddys:install
php artisan ddys:install --views
```

然后检查 `config/ddys.php`，按需设置环境变量：

```env
DDYS_API_BASE_URL=https://ddys.io/api/v1
DDYS_SITE_BASE_URL=https://ddys.io
DDYS_API_KEY=
DDYS_REQUEST_FORM_ENABLED=false
DDYS_DIAGNOSTICS_ENABLED=false
```

## 路由

默认路由前缀是 `/ddys`：

```text
/ddys
/ddys/latest
/ddys/hot
/ddys/movies
/ddys/search?q=interstellar
/ddys/suggest?q=interstellar
/ddys/calendar?year=2026&month=7
/ddys/movie/this-tempting-madness
/ddys/movie/this-tempting-madness/sources
/ddys/movie/this-tempting-madness/related
/ddys/movie/this-tempting-madness/comments
/ddys/collections
/ddys/collection/best-sci-fi
/ddys/shares
/ddys/share/1
/ddys/requests
/ddys/activities
/ddys/user/demo
/ddys/types
/ddys/genres
/ddys/regions
```

本地 JSON 代理：

```text
/ddys-api/latest?limit=12
/ddys-api/movie?slug=this-tempting-madness
/ddys-api/share?id=1
```

## Blade

```blade
@ddys('latest', ['limit' => 12])
@ddysLatest(['limit' => 12])
@ddysMovie(['slug' => 'this-tempting-madness'])
@ddysRequestForm()

<x-ddys::widget view="hot" :params="['limit' => 10]" />
<x-ddys::latest :params="['limit' => 12]" />
<x-ddys::hot :params="['limit' => 10]" />
<x-ddys::movies :params="['type' => 'movie']" />
<x-ddys::search />
<x-ddys::request-form />
```

## 短代码

```text
[ddys_movies type="movie" per_page="24"]
[ddys_latest type="movie" limit="12"]
[ddys_hot limit="10"]
[ddys_search]
[ddys_suggest q="interstellar"]
[ddys_calendar year="2026" month="7"]
[ddys_movie slug="this-tempting-madness"]
[ddys_sources slug="this-tempting-madness"]
[ddys_related slug="this-tempting-madness"]
[ddys_comments slug="this-tempting-madness" per_page="20"]
[ddys_collections per_page="10"]
[ddys_collection slug="best-sci-fi"]
[ddys_shares per_page="10"]
[ddys_share id="1"]
[ddys_requests per_page="10"]
[ddys_activities per_page="10"]
[ddys_user username="demo"]
[ddys_types]
[ddys_genres]
[ddys_regions]
[ddys_request_form]
```

## PHP 调用

```php
use Ddys\Laravel\Facades\Ddys;

$latest = Ddys::latest(['limit' => 12]);
$movie = Ddys::movie('this-tempting-madness');

echo ddys_render('hot', ['limit' => 10]);
echo ddys_shortcode('[ddys_latest limit="6"]');
```

## Artisan 命令

```bash
php artisan ddys:install
php artisan ddys:install --views
php artisan ddys:test
php artisan ddys:routes
php artisan ddys:clear-cache
```

## 本地检查

```powershell
node tools/check.mjs
node --test tests/*.test.mjs
powershell -ExecutionPolicy Bypass -File tools/build-package.ps1
```

检查覆盖 Composer 元数据、Laravel 自动发现、ServiceProvider 行为、路由、控制器、客户端方法、Blade 指令/组件、短代码、渲染器、求片表单安全、缓存回退、命令、资源、图标、文档、打包安全和敏感文本。
