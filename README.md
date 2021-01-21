![Build Status](https://github.com/simplesamlphp/simplesamlphp-module-metaedit/workflows/CI/badge.svg?branch=master)
[![Coverage Status](https://codecov.io/gh/simplesamlphp/simplesamlphp-module-metaedit/branch/master/graph/badge.svg)](https://codecov.io/gh/simplesamlphp/simplesamlphp-module-metaedit)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/simplesamlphp/simplesamlphp-module-metaedit/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/simplesamlphp/simplesamlphp-module-metaedit/?branch=master)

Metaedit module
===============

This module provides a web interface for very basic metadata editing and registration.

Installation
------------

Once you have installed SimpleSAMLphp, installing this module is very simple. Just execute the following
command in the root of your SimpleSAMLphp installation:

```
composer.phar require simplesamlphp/simplesamlphp-module-metaedit:dev-master
```

where `dev-master` instructs Composer to install the `master` branch from the Git repository. See the
[releases](https://github.com/simplesamlphp/simplesamlphp-module-metaedit/releases) available if you
want to use a stable version of the module.

The module is enabled by default. If you want to disable the module once installed, you just need to create a file named
`disable` in the `modules/metaedit/` directory inside your SimpleSAMLphp installation.
