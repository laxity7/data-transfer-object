<?php

/**
 * Created by Vlad Varlamov (laxity.ru) on 24.08.2022.
 */

namespace Laxity7\Test\dtos;

use Laxity7\BaseDTO;
use Laxity7\Test\dtos\foo\FooDto;

/**
 * Class RootDto
 *
 * @property-read  string $patronymic
 */
class RootDto extends BaseDTO
{
    public int $id;
    public string $firstName;
    private string $lastName;
    protected string $patronymic;
    public array $data;
    public ChildDto $child;
    public ?ChildDto $subChild = null;
    /** @var ChildDto[] */
    public array $children = [];
    /** @var FooDto[] */
    public array $foo = [];
    /** @var \Laxity7\Test\dtos\foo\FooDto[] */
    public array $fooBar = [];

    protected function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }
}
