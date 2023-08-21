<?php

/**
 * @link      https://www.github.com/laxity7/dto
 * @copyright Copyright (c) 2022 Vlad Varlamov <work@laxity.ru>
 * @license   https://opensource.org/licenses/MIT
 */

namespace Laxity7;

use JsonSerializable;

/**
 * Class BaseDTO
 *
 * @see BaseDTOTest
 */
abstract class BaseDTO implements JsonSerializable
{
    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        if (empty($attributes)) {
            return;
        }

        foreach ($attributes as $field => $value) {
            $this->_setValue($field, $value);
        }
    }

    /**
     * Get field list
     *
     * @return string[]
     */
    public function fields(): array
    {
        return FieldExtractor::getFields($this);
    }

    /**
     * Get DTO attributes (name => value)
     *
     * @return array
     */
    public function toArray(): array
    {
        $attributes = [];

        foreach ($this->fields() as $field) {
            $attributes[$field] = $this->__get($field);
        }

        return $attributes;
    }

    /**
     * @param string $name
     *
     * @return mixed
     * @throws UnknownPropertyException
     */
    public function __get(string $name): mixed
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        throw new UnknownPropertyException(static::class, $name);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return in_array($name, $this->fields(), true);
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @throws UnknownPropertyException
     */
    public function __set(string $name, mixed $value): void
    {
        $setter = 'set' . $name;
        if (!is_callable([$this, $setter])) {
            throw new UnknownPropertyException(static::class, $name);
        }
        $value = TypeCaster::typeCastValue($this, $name, $value);
        $this->$setter($value);
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @throws UnknownPropertyException
     */
    private function _setValue(string $name, mixed $value): void
    {
        $value = TypeCaster::typeCastValue($this, $name, $value);

        $setter = 'set' . $name;
        if (is_callable([$this, $setter])) {
            $this->$setter($value);

            return;
        }

        if (property_exists($this, $name)) {
            $this->{$name} = $value;

            return;
        }

        if (!$this->ignoreUndefinedFields()) {
            throw new UnknownPropertyException(static::class, $name);
        }
    }

    /** @inheritDoc */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    protected function ignoreUndefinedFields(): bool
    {
        return true;
    }
}
