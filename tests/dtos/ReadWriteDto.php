<?php

/**
 * Created by Vlad Varlamov (laxity.ru) on 24.08.2022.
 */

namespace Laxity7\Test\dtos;

use Laxity7\BaseDTO;

/**
 * Class ReadWriteDto
 *
 * @property int    $id
 * @property string $firstname
 * @property string $lastname
 */
class ReadWriteDto extends BaseDTO
{
    protected int $id;
    protected string $firstname;
    protected string $lastname;

    /**
     * @param string|null $firstname
     */
    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    protected function setLastname(string $value)
    {
        $this->lastname = $value . ' jr.';
    }
}
