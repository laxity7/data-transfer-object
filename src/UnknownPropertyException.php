<?php

/**
 * Created by Vlad Varlamov (laxity.ru) on 24.08.2022.
 */

namespace Laxity7;

use Exception;

class UnknownPropertyException extends Exception
{
    public function __construct(string $dtoClass, string $field)
    {
        parent::__construct(sprintf('Unknown property provided to "%s:%s"', $dtoClass, $field));
    }
}
