# Proxy module

![CI](https://github.com/City-of-Helsinki/drupal-module-helfi-proxy/workflows/CI/badge.svg)
[![codecov](https://codecov.io/gh/City-of-Helsinki/drupal-module-helfi-proxy/branch/main/graph/badge.svg?token=K3S899VYYB)](https://codecov.io/gh/City-of-Helsinki/drupal-module-helfi-proxy)

Provides various fixes to allow multiple Drupal instances to be served from one domain.

## Requirements

- PHP 8.0 or higher

## Features

### Default proxy domain

Redirects all requests to configured proxy domain. See [src/EventSubscriber/RedirectResponseSubscriber.php](/src/EventSubscriber/RedirectResponseSubscriber.php).

Populate `helfi_proxy.settings.default_proxy_domain` settings:

`$config['helfi_proxy.settings']['default_proxy_domain'] = 'helfi-proxy.docker.so';`

_NOTE_: **Using this feature should be your last resort and not recommended by default.**

### Site prefix

A "prefix" will be added to all URLs. This prefix should be same as the `<project prefix>` in `<proxy url>/<project prefix>`.

These can be configured by creating a `conf/cmi/helfi_proxy.settings.yml` file containing:

```yaml
prefixes:
  en: maps-and-transport
  fi: kartat-ja-liikenne
  sv: kartor-och-trafik
  ru: maps
```
or by adding them to your settings.php:

```php
$config['helfi_proxy.settings']['prefixes'] = [
  'en' => 'test-maps-and-transport',
  'fi' => 'test-kartat-ja-liikenne',
  'sv' => 'test-kartor-och-trafik',
  'ru' => 'test-maps',
];
```
### Cookie name suffix

Cookie name will be suffixed with the value configured in `session_suffix` setting. The value will default to site's hostname if not configured. For example `SSID{sha256}helfi-docker-so`.

### Serve assets from asset path

All assets are served from the path configured in `asset-path` setting. For example `liikenne-assets`.

This ensures that all local assets are served directly from the asset path. For example `/sites/default/files/styles/xxx/style.jpg` will be served from `/liikenne-assets/sites/default/files/styles/xxx/style.jpg` instead.

Your project's web server must be able to serve files from that path. If you are using the default Docker images from [City-of-Helsinki/drupal-docker-images](https://github.com/City-of-Helsinki/drupal-docker-images) then you don't have to worry about this. Otherwise, add:
```
location ~ ^/(?:.*)-assets/(.*)$ {
  proxy_redirect off;
  proxy_set_header Host $host;
  proxy_set_header X-Forwarded-Proto https;
  proxy_set_header X-Real-IP $remote_addr;
  proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
  proxy_pass http://127.0.0.1:8080/$1$is_args$args;
}
```
or something equivalent to your web server configuration.

### Disallow search engines/robots from indexing the site

Set `helfi_proxy.settings.robots_header_enabled` configuration to `TRUE`.

```php
if ($robots_header_enabled = getenv('DRUPAL_X_ROBOTS_TAG_HEADER')) {
  $config['helfi_proxy.settings']['robots_header_enabled'] = (bool) $robots_header_enabled;
}
```

This will insert a `X-Robots-Tag: noindex, nofollow` header to every response, effectively blocking search engines from indexing the site.

You can use `helfi_proxy.settings` configuration to only ignore certain paths:

```yaml
robots_paths:
  - '/user/login'
  - '/residential-*'
```

### Varnish support

Enable required modules: `drush en varnish_purger varnish_purge_tags purge_drush purge_processor_cron purge_queuer_coretags purge_tokens`.

Copy configuration from `helfi_proxy/config/optional` to your `conf/cmi` folder if you enabled this module before Varnish/Purge modules.

Add these to your settings.php to use varnish cache:

```php
if ($varnish_host = getenv('DRUPAL_VARNISH_HOST')) {
  $config['varnish_purger.settings.default']['hostname'] = $varnish_host;
  $config['varnish_purger.settings.varnish_purge_all']['hostname'] = $varnish_host;
}

if ($varnish_port = getenv('DRUPAL_VARNISH_PORT')) {
  $config['varnish_purger.settings.default']['port'] = $varnish_port;
  $config['varnish_purger.settings.varnish_purge_all']['port'] = $varnish_port;
}

// Configuration doesn't know about existing config yet so we can't
// just append new headers to an already existing headers array here.
// If you have configured any extra headers in your purge settings
// you must add them here as well.
$config['varnish_purger.settings.default']['headers'] = [
  [
    'field' => 'Cache-Tags',
    'value' => '[invalidation:expression]',
  ],
];
$config['varnish_purger.settings.varnish_purge_all']['headers'] = [
  [
    'field' => 'X-VC-Purge-Method',
    'value' => 'regex',
  ],
];

if ($varnish_purge_key = getenv('VARNISH_PURGE_KEY')) {
  $config['varnish_purger.settings.default']['headers'][] = [
    'field' => 'X-VC-Purge-Key',
    'value' => $varnish_purge_key,
  ];
  $config['varnish_purger.settings.varnish_purge_all']['headers'][] = [
    'field' => 'X-VC-Purge-Key',
    'value' => $varnish_purge_key,
  ];
}
```

## Testing reverse proxy on local

See [City-of-Helsinki/drupal-helfi-local-proxy](https://github.com/City-of-Helsinki/drupal-helfi-local-proxy) for more information.

## Contact

Slack: #helfi-drupal (http://helsinkicity.slack.com/)

Mail: helfi-drupal-aaaactuootjhcono73gc34rj2u@druid.slack.com
