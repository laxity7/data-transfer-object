<?php

/**
 * Created by Vlad Varlamov (laxity.ru) on 24.08.2022.
 */

namespace Laxity7\Test\dtos;

use Laxity7\DataTransferObject;

/**
 * Class ReadWriteDto
 *
 * @property int    $id
 * @property string $firstname
 * @property string $lastname
 */
class ReadWriteDto extends DataTransferObject
{
    protected int $id;
    protected string $firstname;
    protected string $lastname;

    /**
     * @param string $firstname
     */
    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    protected function getLastname(): string
    {
        return $this->lastname . ' jr.';
    }
}
