import { promises as fs } from 'node:fs';
import path from 'node:path';
import process from 'node:process';

const root = process.cwd();
const failures = [];

const requiredFiles = [
  'README.md',
  'README.zh-CN.md',
  'LICENSE',
  '.gitignore',
  'composer.json',
  'config/ddys.php',
  'routes/ddys.php',
  'src/DdysServiceProvider.php',
  'src/Client.php',
  'src/CacheStore.php',
  'src/Renderer.php',
  'src/Shortcode.php',
  'src/PageService.php',
  'src/RequestService.php',
  'src/helpers.php',
  'src/Facades/Ddys.php',
  'src/Exceptions/DdysException.php',
  'src/Support/Security.php',
  'src/Http/Controllers/PageController.php',
  'src/Http/Controllers/ProxyController.php',
  'src/Http/Controllers/RequestController.php',
  'src/Http/Controllers/DiagnosticsController.php',
  'src/View/Components/Widget.php',
  'src/View/Components/Latest.php',
  'src/View/Components/Hot.php',
  'src/View/Components/Movies.php',
  'src/View/Components/Search.php',
  'src/View/Components/RequestForm.php',
  'src/Commands/DiagnosticsCommand.php',
  'src/Commands/ClearCacheCommand.php',
  'src/Commands/RoutesCommand.php',
  'src/Commands/InstallCommand.php',
  'resources/views/page.blade.php',
  'resources/views/diagnostics.blade.php',
  'resources/views/components/widget.blade.php',
  'resources/assets/css/frontend.css',
  'resources/assets/js/frontend.js',
  'resources/assets/images/icon-16.png',
  'resources/assets/images/icon-32.png',
  'resources/assets/images/icon-192.png',
  'resources/assets/images/icon-512.png',
  'resources/assets/images/logo.png',
  'tests/structure.test.mjs',
  'tools/build-package.ps1',
  'tools/check.mjs'
];

const shortcodes = [
  'ddys_movies',
  'ddys_latest',
  'ddys_hot',
  'ddys_search',
  'ddys_suggest',
  'ddys_calendar',
  'ddys_movie',
  'ddys_sources',
  'ddys_related',
  'ddys_comments',
  'ddys_collections',
  'ddys_collection',
  'ddys_shares',
  'ddys_share',
  'ddys_requests',
  'ddys_activities',
  'ddys_user',
  'ddys_types',
  'ddys_genres',
  'ddys_regions',
  'ddys_request_form'
];

const pageViews = [
  'movies',
  'latest',
  'hot',
  'search',
  'suggest',
  'calendar',
  'movie',
  'sources',
  'related',
  'comments',
  'collections',
  'collection',
  'shares',
  'share',
  'requests',
  'activities',
  'user',
  'types',
  'genres',
  'regions'
];

for (const file of requiredFiles) {
  await mustExist(file);
}

await checkEncoding();
await checkPhpStructure();
await checkComposer();
await checkConfigAndProvider();
await checkRoutesAndControllers();
await checkClientAndSecurity();
await checkRendererAndShortcodes();
await checkBladeAndCommands();
await checkAssets();
await checkDocs();
await checkBuildScript();
await checkForbiddenFiles();
await checkForbiddenText();

if (failures.length > 0) {
  console.error(failures.map((failure) => `- ${failure}`).join('\n'));
  process.exit(1);
}

console.log(JSON.stringify({ ok: true, files: (await listFiles(root)).length, shortcodes: shortcodes.length, views: pageViews.length }, null, 2));

async function mustExist(rel) {
  try {
    await fs.stat(path.join(root, rel));
  } catch {
    failures.push(`Missing required file: ${rel}`);
  }
}

async function checkEncoding() {
  for (const full of await listFiles(root)) {
    const rel = slash(path.relative(root, full));
    if (!isTextFile(rel)) continue;
    const buffer = await fs.readFile(full);
    assert(!(buffer[0] === 0xef && buffer[1] === 0xbb && buffer[2] === 0xbf), `${rel} must not contain UTF-8 BOM.`);
    const text = buffer.toString('utf8');
    assert(!text.includes('\uFFFD'), `${rel} contains Unicode replacement characters.`);
    if (/\.php$/i.test(rel) && !/\.blade\.php$/i.test(rel)) {
      assert(!text.trimEnd().endsWith('?>'), `${rel} must not include a closing PHP tag.`);
    }
  }
}

async function checkPhpStructure() {
  for (const full of await listFiles(root)) {
    const rel = slash(path.relative(root, full));
    if (!/\.php$/i.test(rel) || /\.blade\.php$/i.test(rel)) continue;
    const text = await read(rel);
    assert(text.trimStart().startsWith('<?php'), `${rel} must start with <?php.`);
    if (rel.startsWith('src/') && rel !== 'src/helpers.php') {
      assert(text.includes('namespace Ddys\\Laravel'), `${rel} must use the Ddys\\Laravel namespace.`);
    }
    const error = scanPhpDelimiters(text);
    assert(!error, `${rel} has unbalanced delimiter: ${error}`);
  }
}

async function checkComposer() {
  const composer = JSON.parse(await read('composer.json'));
  assert(composer.name === 'ddysiodev/ddys-laravel-package', 'composer package name mismatch.');
  assert(composer.type === 'library', 'composer type must be library.');
  assert(composer.autoload?.['psr-4']?.['Ddys\\Laravel\\'] === 'src/', 'composer must PSR-4 autoload Ddys\\Laravel.');
  assert(composer.autoload?.files?.includes('src/helpers.php'), 'composer must autoload helpers.php.');
  assert(composer.extra?.laravel?.providers?.includes('Ddys\\Laravel\\DdysServiceProvider'), 'composer must define Laravel auto-discovery provider.');
  for (const dep of ['illuminate/cache', 'illuminate/console', 'illuminate/http', 'illuminate/routing', 'illuminate/support', 'illuminate/validation', 'illuminate/view']) {
    assert(composer.require?.[dep], `composer require missing ${dep}`);
  }
}

async function checkConfigAndProvider() {
  const config = await read('config/ddys.php');
  for (const fragment of [
    'DDYS_API_BASE_URL',
    'DDYS_API_KEY',
    "'prefix' => env('DDYS_ROUTE_PREFIX', 'ddys')",
    "'prefix' => env('DDYS_PROXY_PREFIX', 'ddys-api')",
    "'allow_routes'",
    "'request_form'",
    "'honeypot_field'",
    "'allowed_resource_protocols'"
  ]) {
    assert(config.includes(fragment), `config missing ${fragment}`);
  }

  const provider = await read('src/DdysServiceProvider.php');
  for (const fragment of [
    'mergeConfigFrom',
    'loadRoutesFrom',
    'loadViewsFrom',
    'Blade::directive',
    'Blade::componentNamespace',
    'stripDirectiveExpression',
    'publishes',
    'ddys-config',
    'ddys-assets',
    'commands',
    'DiagnosticsCommand',
    'ClearCacheCommand',
    'RoutesCommand',
    'InstallCommand'
  ]) {
    assert(provider.includes(fragment), `ServiceProvider missing ${fragment}`);
  }
  for (const name of shortcodes) {
    assert(provider.includes(`'${name}'`), `ServiceProvider missing Blade directive source ${name}`);
  }
}

async function checkRoutesAndControllers() {
  const routes = await read('routes/ddys.php');
  for (const view of pageViews) {
    assert(routes.includes(`'${view}'`) || routes.includes(`/${view}`), `routes missing ${view}`);
  }
  for (const fragment of ['request-submit', 'ddys-api', 'ProxyController', 'DiagnosticsController', 'whereNumber', 'where(']) {
    assert(routes.includes(fragment), `routes missing ${fragment}`);
  }
  assert(routes.includes("if (config('ddys.diagnostics.enabled', false))"), 'diagnostics routes must only register when enabled.');

  const page = await read('src/Http/Controllers/PageController.php');
  assert(page.includes("$request->route('ddysView'") && page.includes("$request->route('slug'"), 'PageController must read named route parameters.');
  const proxy = await read('src/Http/Controllers/ProxyController.php');
  assert(proxy.includes('JsonResponse') && proxy.includes('$this->client->proxy'), 'ProxyController must return client proxy JSON.');
  const request = await read('src/Http/Controllers/RequestController.php');
  assert(request.includes('RequestService') && request.includes('success'), 'RequestController must submit request service.');
  const diagnostics = await read('src/Http/Controllers/DiagnosticsController.php');
  assert(diagnostics.includes("config('ddys.diagnostics.enabled'") && diagnostics.includes('$this->cache->clear()'), 'DiagnosticsController must be gated and clear cache.');
}

async function checkClientAndSecurity() {
  const client = await read('src/Client.php');
  for (const method of ['movies', 'latest', 'hot', 'search', 'suggest', 'calendar', 'movie', 'sources', 'related', 'comments', 'collections', 'collection', 'shares', 'share', 'requests', 'activities', 'user', 'types', 'genres', 'regions', 'createRequest', 'createComment', 'deleteComment', 'reportInvalidResource', 'follow', 'unfollow', 'me']) {
    assert(client.includes(`function ${method}`), `Client missing ${method}()`);
  }
  for (const fragment of ['withToken', 'timeout', 'retry', 'withUserAgent', 'proxyPath', 'allow_routes', "preg_match('/^[1-9][0-9]*$/'"]) {
    assert(client.includes(fragment), `Client missing ${fragment}`);
  }
  assert(!client.includes('throw: false'), 'Client retry call must avoid Laravel-version-specific named arguments.');

  const cache = await read('src/CacheStore.php');
  for (const fragment of ['function store', 'supportsTags', 'tags([$this->tag()])', 'rememberKey', 'ttlForPath', 'dictionary_ttl', 'community_ttl']) {
    assert(cache.includes(fragment), `CacheStore missing ${fragment}`);
  }

  const security = await read('src/Support/Security.php');
  for (const fragment of ['normalizeBaseUrl', 'parse_url', 'safeMediaUrl', "substr($value, 0, 120)", "substr($value, 0, 64)"]) {
    assert(security.includes(fragment), `Security missing ${fragment}`);
  }
  const config = await read('config/ddys.php');
  assert(config.includes('allowed_resource_protocols'), 'config must define allowed resource protocols.');

  const request = await read('src/RequestService.php');
  for (const fragment of ['ValidatorFactory', 'RateLimiter', 'honeypot_field', 'tooManyAttempts', 'regex:/^tt\\d{1,20}$/i', 'createRequest']) {
    assert(request.includes(fragment), `RequestService missing ${fragment}`);
  }
}

async function checkRendererAndShortcodes() {
  const renderer = await read('src/Renderer.php');
  for (const fragment of ['renderList', 'renderDetail', 'renderSources', 'renderCalendar', 'renderDictionary', 'renderSearch', 'renderRequestForm', 'resourceLinks', 'ddys-laravel-honeypot-wrapper', "config('ddys.display.show_poster'", "config('ddys.display.show_rating'", "config('ddys.display.show_summary'"]) {
    assert(renderer.includes(fragment), `Renderer missing ${fragment}`);
  }

  const shortcode = await read('src/Shortcode.php');
  for (const name of shortcodes) {
    assert(shortcode.includes(`'${name}'`), `Shortcode missing ${name}`);
  }
  assert(shortcode.includes('preg_replace_callback') && shortcode.includes('parseAttributes') && shortcode.includes('html_entity_decode'), 'Shortcode parser must parse attributes safely.');

  const page = await read('src/PageService.php');
  for (const view of pageViews) {
    assert(page.includes(`'${view}'`), `PageService missing ${view}`);
  }
}

async function checkBladeAndCommands() {
  const component = await read('src/View/Components/Widget.php');
  assert(component.includes('class Widget') && component.includes("view('ddys::components.widget'"), 'Widget component must render package component view.');
  for (const componentName of ['Latest', 'Hot', 'Movies', 'Search', 'RequestForm']) {
    const text = await read(`src/View/Components/${componentName}.php`);
    assert(text.includes('extends Widget'), `${componentName} component must extend Widget.`);
  }
  const helpers = await read('src/helpers.php');
  for (const fn of ['ddys_client', 'ddys_render', 'ddys_shortcode', 'ddys_request_form']) {
    assert(helpers.includes(`function ${fn}`), `helpers missing ${fn}`);
  }
  for (const cmd of ['DiagnosticsCommand', 'ClearCacheCommand', 'RoutesCommand', 'InstallCommand']) {
    const text = await read(`src/Commands/${cmd}.php`);
    assert(text.includes('protected $signature') && text.includes('function handle'), `${cmd} must define signature and handle.`);
  }
  const install = await read('src/Commands/InstallCommand.php');
  assert(install.includes('{--views') && install.includes('ddys-views'), 'InstallCommand must optionally publish views.');
}

async function checkAssets() {
  const expected = {
    'resources/assets/images/icon-16.png': [16, 16],
    'resources/assets/images/icon-32.png': [32, 32],
    'resources/assets/images/icon-192.png': [192, 192],
    'resources/assets/images/icon-512.png': [512, 512],
    'resources/assets/images/logo.png': [512, 512]
  };
  for (const [rel, size] of Object.entries(expected)) {
    const actual = await pngSize(rel);
    assert(actual[0] === size[0] && actual[1] === size[1], `${rel} must be ${size[0]}x${size[1]}, got ${actual[0]}x${actual[1]}.`);
  }
  const css = await read('resources/assets/css/frontend.css');
  const js = await read('resources/assets/js/frontend.js');
  assert(css.includes('ddys-laravel-items') && css.includes('ddys-laravel-honeypot-wrapper'), 'frontend.css must include layout and honeypot styles.');
  assert(js.includes('data-ddys-laravel-request-form') && js.includes('fetch(form.action') && !js.includes('api_key') && !js.includes('Authorization'), 'frontend.js must submit forms without exposing API credentials.');
}

async function checkDocs() {
  const en = await read('README.md');
  const zh = await read('README.zh-CN.md');
  assert(en.includes('[中文](README.zh-CN.md)') && zh.includes('[English](README.md)'), 'READMEs must link to each other.');
  assert(en.includes('ddys-laravel-package') && zh.includes('ddys-laravel-package'), 'READMEs must include package/repo name.');
  assert(en.includes('Official Laravel package') && zh.includes('官方 Laravel 扩展包'), 'READMEs must clearly describe the package.');
  assert(!en.includes('npm install') && !zh.includes('npm install'), 'Laravel package README must not mention npm install.');
  for (const name of shortcodes) {
    assert(en.includes(`[${name}`) && zh.includes(`[${name}`), `READMEs missing shortcode ${name}`);
  }
  for (const view of ['/ddys/latest', '/ddys/hot', '/ddys/movies', '/ddys/search', '/ddys/calendar', '/ddys/movie/', '/ddys/collections', '/ddys/shares', '/ddys/requests', '/ddys/types', '/ddys/genres', '/ddys/regions']) {
    assert(en.includes(view) && zh.includes(view), `READMEs missing frontend path ${view}`);
  }
}

async function checkBuildScript() {
  const script = await read('tools/build-package.ps1');
  assert(script.includes('ZipFileExtensions') && script.includes('ddys-laravel-package-v{0}.zip') && script.includes('StartsWith($resolvedRoot') && script.includes('Replace("\\", "/")'), 'build-package.ps1 must safely create portable release zip.');
}

async function checkForbiddenFiles() {
  for (const full of await listFiles(root)) {
    const rel = slash(path.relative(root, full));
    assert(!/(^|\/)(\.env|\.env\..*|node_modules|vendor|cache|tmp|dist)(\/|$)/.test(rel), `Forbidden generated or sensitive path committed: ${rel}`);
    assert(!/\.(log|bak|tmp|cache)$/i.test(rel), `Forbidden generated file committed: ${rel}`);
  }
}

async function checkForbiddenText() {
  const patterns = ['ghp_', 'github_pat_', 'npm_', '\uFFFD', '\u{6d93}\u{e15f}\u{6783}', '\u{6d63}\u{5ea3}\u{e06c}', '\u{7039}\u{6a3b}\u{67df}', '\u{942d}\u{e15d}\u{552c}', '\u{59ab}\u{20ac}\u{93cc}'];
  for (const full of await listFiles(root)) {
    const rel = slash(path.relative(root, full));
    if (!isTextFile(rel) || rel === 'tools/check.mjs') continue;
    const text = await read(rel);
    for (const pattern of patterns) {
      assert(!text.includes(pattern), `${rel} contains forbidden text pattern ${pattern}`);
    }
  }
}

async function read(rel) {
  return fs.readFile(path.join(root, rel), 'utf8');
}

async function listFiles(dir) {
  const entries = await fs.readdir(dir, { withFileTypes: true });
  const out = [];
  for (const entry of entries) {
    if (['.git', 'dist', 'node_modules', 'vendor'].includes(entry.name)) continue;
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) out.push(...await listFiles(full));
    else out.push(full);
  }
  return out;
}

async function pngSize(rel) {
  const buffer = await fs.readFile(path.join(root, rel));
  assert(buffer.readUInt32BE(0) === 0x89504e47, `${rel} is not a PNG.`);
  return [buffer.readUInt32BE(16), buffer.readUInt32BE(20)];
}

function scanPhpDelimiters(text) {
  const stack = [];
  const pairs = { ')': '(', ']': '[', '}': '{' };
  let state = 'code';
  let quote = '';
  let line = 1;
  let escaped = false;
  for (let i = 0; i < text.length; i++) {
    const ch = text[i];
    const next = text[i + 1] || '';
    if (ch === '\n') {
      line++;
      if (state === 'line-comment') state = 'code';
      continue;
    }
    if (state === 'block-comment') {
      if (ch === '*' && next === '/') {
        state = 'code';
        i++;
      }
      continue;
    }
    if (state === 'line-comment') continue;
    if (state === 'string') {
      if (escaped) {
        escaped = false;
        continue;
      }
      if (ch === '\\') {
        escaped = true;
        continue;
      }
      if (ch === quote) {
        state = 'code';
        quote = '';
      }
      continue;
    }
    if ((ch === '/' && next === '/') || ch === '#') {
      state = 'line-comment';
      if (ch === '/') i++;
      continue;
    }
    if (ch === '/' && next === '*') {
      state = 'block-comment';
      i++;
      continue;
    }
    if (ch === '\'' || ch === '"') {
      state = 'string';
      quote = ch;
      continue;
    }
    if ('([{'.includes(ch)) {
      stack.push({ ch, line });
      continue;
    }
    if (')]}'.includes(ch)) {
      const top = stack.pop();
      if (!top || top.ch !== pairs[ch]) return `${ch} at line ${line}`;
    }
  }
  if (state === 'string') return `unterminated string at line ${line}`;
  if (state === 'block-comment') return `unterminated block comment at line ${line}`;
  if (stack.length > 0) {
    const top = stack[stack.length - 1];
    return `${top.ch} opened at line ${top.line}`;
  }
  return '';
}

function isTextFile(rel) {
  return /\.(php|blade\.php|yml|json|js|css|md|txt|ps1|mjs)$/i.test(rel) || rel === '.gitignore' || rel === 'LICENSE';
}

function slash(value) {
  return value.replace(/\\/g, '/');
}

function assert(condition, message) {
  if (!condition) failures.push(message);
}
