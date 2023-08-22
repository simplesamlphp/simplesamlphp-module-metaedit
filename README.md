![Build Status](https://github.com/simplesamlphp/simplesamlphp-module-metaedit/workflows/CI/badge.svg?branch=master)
[![Coverage Status](https://codecov.io/gh/simplesamlphp/simplesamlphp-module-metaedit/branch/master/graph/badge.svg)](https://codecov.io/gh/simplesamlphp/simplesamlphp-module-metaedit)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/simplesamlphp/simplesamlphp-module-metaedit/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/simplesamlphp/simplesamlphp-module-metaedit/?branch=master)
[![Type Coverage](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-metaedit/coverage.svg)](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-metaedit)
[![Psalm Level](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-metaedit/level.svg)](https://shepherd.dev/github/simplesamlphp/simplesamlphp-module-metaedit)

# Metaedit module

This module provides a web interface for very basic metadata editing and registration.

## Install

Once you have installed SimpleSAMLphp, installing this module is very simple. Just execute the following
command in the root of your SimpleSAMLphp installation:

```bash
    vendor/bin/composer require simplesamlphp/simplesamlphp-module-metaedit
```

## Configuration

Next thing you need to do is to enable the module: in `config.php`,
search for the `module.enable` key and set `metaedit` to true:

```php
    'module.enable' => [
        'metaedit' => true,
        …
    ],
```
