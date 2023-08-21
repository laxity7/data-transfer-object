<?php

namespace Laxity7\Test\dtos;

use Laxity7\DataTransferObject;

class ReadonlyDto extends DataTransferObject
{
    public function __construct(
        readonly public string $foo,
        readonly public string $bar,
    ) {
        parent::__construct();
    }
}
