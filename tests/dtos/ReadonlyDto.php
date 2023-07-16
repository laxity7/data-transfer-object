<?php

namespace Laxity7\Test\dtos;

use Laxity7\BaseDTO;

class ReadonlyDto extends BaseDTO
{
    public function __construct(
        readonly public string $foo,
        readonly public string $bar,
    ) {
        parent::__construct();
    }
}
