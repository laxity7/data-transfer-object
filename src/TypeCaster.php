<?php
/**
 * Created by Vlad Varlamov (laxity.ru) on 24.08.2022.
 */

namespace Laxity7;

use ReflectionClass;

final class TypeCaster
{
    /**
     * @var array<string, string|null> class mapping cache
     */
    private static array $classes = [];
    /**
     * @var array<string, array<string, Field> class fields cache
     */
    private static $fields = [];
    /**
     * @var array<string, ReflectionClass>
     */
    private static $reflections = [];

    private static function loadFields(object $object): array
    {
        $className = $object::class;
        if (isset(self::$fields[$className])) {
            return self::$fields[$className];
        }

        $fields = [];

        $reflection = self::getReflectionsClass($object);
        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();

            if ($property->isStatic()) {
                continue;
            }

            $isArray = false;
            $type = $property->getType()?->getName();
            if (!$type || $type === 'array') {
                $comments = $property->getDocComment();
                if (empty($comments)) {
                    continue;
                }
                preg_match('/@var ((?:[\w?|\\\\,]+(?:\[])?)+)/', $comments, $matches);
                $definition = trim($matches[1] ?? '');
                $type = rtrim($definition, '[]');
                $isArray = str_contains($definition, '[]');
            }

            $type = self::normalizeType($reflection, $type);
            if (!$type) {
                continue;
            }

            $fields[$name] = new Field(
                name: $name,
                type: $type,
                isArray: $isArray
            );
        }

        self::$fields[$className] = $fields;

        return $fields;
    }

    /**
     * @param object $object Dto object
     * @param string $name Attribute name
     * @param mixed $value Attribute value
     *
     * @return mixed Attribute value with cast type
     */
    public static function typeCastValue(object $object, string $name, mixed $value): mixed
    {
        if ($value === null || is_object($value)) {
            return $value;
        }

        $fields = self::loadFields($object);
        $field = $fields[$name] ?? null;

        if ($field === null) {
            return $value;
        }

        if (!$field->isArray) {
            $newValue = self::makeInstance($field->type, $value);
        } else {
            $newValue = [];
            foreach ($value as $key => $item) {
                $newValue[$key] = is_array($item) ? self::makeInstance($field->type, $item) : $item;
            }
        }

        return $newValue;
    }

    /**
     * Instantiate class
     *
     * @param string $class Class namespace
     * @param array $values Array of values
     * @return object
     */
    private static function makeInstance(string $class, array $values): object
    {
        $reflection = self::getReflectionsClass($class);
        $params = $reflection->getConstructor()?->getParameters();

        if ($params === null) {
            $object = $reflection->newInstance();
            foreach ($values as $name => $value) {
                $object->{$name} = $value;
            }

            return $object;
        }

        if (count($params) === 1 && $params[0]->getType()?->getName() === 'array') {
            return $reflection->newInstance($values);
        }

        return $reflection->newInstanceArgs($values);
    }

    private static function getReflectionsClass(string|object $class): ReflectionClass
    {
        $key = is_string($class) ? $class : $class::class;
        if (!isset(self::$reflections[$key])) {
            self::$reflections[$key] = new ReflectionClass($class);
        }

        return self::$reflections[$key];
    }

    /**
     * Normalize class namespace
     *
     * @param DataTransferObject $dto
     * @param string $type
     * @return string|null Class namespace
     */
    private static function normalizeType(ReflectionClass $reflectionClass, string $type): ?string
    {
        if (empty($type) || self::isScalar($type)) {
            return null;
        }

        if (isset(self::$classes[$type])) {
            return self::$classes[$type];
        }

        $class = $type;
        if (!str_contains($class, '\\')) {
            $class = $reflectionClass->getNamespaceName() . '\\' . $type;
            if (!class_exists($class)) {
                $classText = file_get_contents($reflectionClass->getFileName());
                preg_match(sprintf('/use (([\w_\\\\])+%s)/', $type), $classText, $matches);

                $class = $matches[1] ?? null;
            }
        }

        $isClass = $class && class_exists($class);
        self::$classes[$type] = $isClass ? $class : null;

        return self::$classes[$type];
    }

    /**
     * Determines if a type is a scalar
     *
     * @param string $type
     * @return bool
     */
    private static function isScalar(string $type): bool
    {
        return in_array($type, ['string', 'int', 'float', 'bool', 'array'], true);
    }
}

/**
 * @internal
 */
final class Field
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $isArray,
    ) {
    }
}
