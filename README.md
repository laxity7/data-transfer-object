# Data Transfer Object

Fast and light Data Transfer Object (DTO) with nested objects and attribute change control, 
without checking scalar types, use PHP type casting to check them.

[![License](https://img.shields.io/github/license/laxity7/dto.svg)](https://github.com/laxity7/dto/blob/master/LICENSE)
[![Latest Stable Version](https://img.shields.io/packagist/v/laxity7/dto.svg)](https://packagist.org/packages/laxity7/dto)
[![Total Downloads](https://img.shields.io/packagist/dt/laxity7/dto.svg)](https://packagist.org/packages/laxity7/dto)

You can control whether the DTO can be mutable, but by default, it is immutable.
To be able to change, use public properties or public setters.

This package supports PHP 8.1+

## Install

Install via composer

```shell
composer require laxity7/dto
```

## How to use

```php
<?php

use Laxity7\DataTransferObject;

require_once 'vendor/autoload.php';

/**
 * @property-read string $time
 */
class FooDto extends DataTransferObject
{
    public int $id;
    public string $name;
    public array $data; // just array
    /** @var BarDto[] */
    public array $bars; // array of objects BarDto
    public BarDto $bar;
    /** @var BarDto */
    public $baz;
    public BarReadonlyDto $readonlyBar;
    public ReadonlyDto $readonly;
    /** @var ReadonlyDto[] */
    public array $readonlyArr;
    protected string $time;

    // setter can be protected/public
    // setter has higher priority than field
    protected function setName(string $name): void
    {
        $this->name = 'Foo' . $name;
    }

    protected function getTime(): string
    {
        return (new DateTime($this->time))->format('H:i:s');
    }
}

// optional to inherit DataTransferObject
class BarDto
{
    public int $id;
}

// optional to inherit DataTransferObject
class BarReadonlyDto
{
    public function __construct(
        readonly public int $id
    ){}
}

class ReadonlyDto extends DataTransferObject
{
    public function __construct(
        readonly public string $foo,
        readonly public string $bar,
    ) {
        parent::__construct();
    }
}

$fooDto = new FooDto([
    'id' => 1,
    'name' => 'Bar', // FooBar
    'data' => [1, 2, 3],
    'bars' => [ // array of objects
        ['id' => 1], // transforms into an object BarDto
        ['id' => 2], // transforms into an object BarDto
    ],
    'bar' => ['id' => 3], // transforms into an object BarDto
    'baz' => new BarReadonlyDto(4), // just set object
    'readonlyBar' => ['id' => 5], // transforms into an object BarReadonlyDto
    'readonly' => [ // transforms into an object ReadonlyDto
        'bar' => 'baz',
        'foo' => 'gaz',
    ],
    'readonlyArr' => [ // array of objects
        ['bar' => 'baz', 'foo' => 'gaz'], // transforms into an object ReadonlyDto
        ['bar' => 'baz1', 'foo' => 'gaz1'], // transforms into an object ReadonlyDto
    ],
    'time' => '05:59',
]);

// Get all attributes
$arr = $fooDto->toArray();
// Serialize to json (also serializes all nested DTOs)
$json = json_encode($fooDto);
```
