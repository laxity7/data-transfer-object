<?php

/**
 * Created by Vlad Varlamov (laxity.ru) on 24.08.2022.
 */

namespace Laxity7\Test\dtos;

use Laxity7\DataTransferObject;

class ChildDto extends DataTransferObject
{
    public int $id;
    public ?string $name = null;

    protected function setName(?string $value): void
    {
        if ($value) {
            $this->name = $value . 'Foo';
        }
    }
}
