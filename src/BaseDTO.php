<?php

/**
 * @link      https://www.github.com/laxity7/dto
 * @copyright Copyright (c) 2022 Vlad Varlamov <work@laxity.ru>
 * @license   https://opensource.org/licenses/MIT
 */

namespace Laxity7;

use JsonSerializable;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Class BaseDTO
 *
 * @see BaseDTOTest
 */
abstract class BaseDTO implements JsonSerializable
{
    protected bool $_ignoreUndefinedFields = true;
    /**
     * @var string[] Field cache
     */
    protected array $_fields = [];

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        if (empty($attributes)) {
            return;
        }

        $attributes = TypeCaster::typeCastNested($this, $attributes);
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
        if ($this->_fields) {
            return $this->_fields;
        }

        $class = new ReflectionClass($this);
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            $name = $method->getName();
            if ($method->isStatic() || !str_starts_with($name, 'get')) {
                continue;
            }

            $this->_fields[] = strtolower($name[3]) . substr($name, 4);
        }

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED) as $property) {
            $name = $property->getName();
            if ($property->isStatic() || str_starts_with($name, '_')) {
                continue;
            }

            $this->_fields[] = $name;
        }

        $this->_fields = array_unique($this->_fields);

        return $this->_fields;
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
            $attributes[$field] = $this->{$field};
        }

        return $attributes;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
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
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return in_array($name, $this->fields(), true);
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @throws UnknownPropertyException
     */
    public function __set(string $name, $value): void
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $reflection = new ReflectionMethod($this, $setter);
            if ($reflection->isPublic()) {
                $this->$setter($value);

                return;
            }
        }

        throw new UnknownPropertyException(static::class, $name);
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @throws UnknownPropertyException
     */
    public function _setValue(string $name, $value): void
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);

            return;
        }
        if (property_exists($this, $name)) {
            $this->{$name} = $value;

            return;
        }

        if (!$this->_ignoreUndefinedFields) {
            throw new UnknownPropertyException(static::class, $name);
        }
    }

    /** @inheritDoc */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return ($haystack[0] === $needle[0]) ? strncmp($haystack, $needle, strlen($needle)) === 0 : false;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
