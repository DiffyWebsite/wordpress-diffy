# Diffy PHP Bindings

The Diffy PHP library provides convenient access to the Diffy API from applications written in PHP language.

## Requirements
PHP 5.6.0 and later.

## Composer
You can install the bindings via [Composer](http://getcomposer.org/). Run the following command:
```bash
composer require diffywebsite/diffy-php
```

To use the bindings, use Composer's [autoload](https://getcomposer.org/doc/01-basic-usage.md#autoloading):

```php
require_once('vendor/autoload.php');
```

## Getting Started

Simple usage looks like:

```php
\Diffy\Diffy::setApiKey('c31fec8e123e479e75d46744c13a7d91');
print_r(\Diffy\Screenshot::create(132, 'production'));
```

See examples.php file for more examples.

## Documentation

See the [Diffy REST API Swagger](https://app.diffy.website/rest).
