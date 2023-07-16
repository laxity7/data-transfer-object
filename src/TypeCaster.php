<?php
/**
 * Created by Vlad Varlamov (laxity.ru) on 24.08.2022.
 */

namespace Laxity7;

use ReflectionClass;

class TypeCaster
{
    /**
     * @var array class mapping cache
     */
    protected static array $classes = [];

    /**
     * Automatic casting of nested DTOs
     *
     * @param array $attributes
     *
     * @return array
     */
    public static function typeCastNested(BaseDTO $dto, array $attributes): array
    {
        $class = new ReflectionClass($dto);
        foreach ($class->getProperties() as $property) {
            $name = $property->getName();
            $value = $attributes[$name] ?? null;

            if ($property->isStatic() || !array_key_exists($name, $attributes) || !is_array($value)) {
                continue;
            }

            $isArray = false;
            $type = $property->getType() ? $property->getType()->getName() : null;
            if (!$type || $type === 'array') {
                $comments = $property->getDocComment();
                if (empty($comments)) {
                    continue;
                }
                preg_match('/@var ((?:[\w?|\\\\,]+(?:\[])?)+)/', $comments, $matches);
                $definition = trim($matches[1] ?? '');
                $type = rtrim($definition, '[]');
                $isArray = $definition !== $type;
            }

            $type = static::normalizeClass($class, $type);
            if (!$type) {
                continue;
            }

            if (!$isArray) {
                $attributes[$name] = self::makeInstance($type, $value);
            } else {
                $attributes[$name] = [];
                foreach ($value as $key => $item) {
                    $attributes[$name][$key] = is_array($item) ? self::makeInstance($type, $item) : $item;
                }
            }
        }

        return $attributes;
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
        $reflection = new ReflectionClass($class);
        $params = $reflection->getConstructor()?->getParameters();

        if ($params === null && !$reflection->isSubclassOf(BaseDTO::class)) {
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

    /**
     * Normalize class namespace
     *
     * @param BaseDTO $dto
     * @param string $typeClass
     * @return string|null Class namespace
     */
    protected static function normalizeClass(ReflectionClass $reflectionClass, string $typeClass): ?string
    {
        if (empty($typeClass)) {
            return null;
        }

        if (isset(static::$classes[$typeClass])) {
            return static::$classes[$typeClass];
        }

        $class = $typeClass;
        if (!str_contains($class, '\\')) {
            $classInCurrentNamespace = $reflectionClass->getNamespaceName() . '\\' . $typeClass;
            if (class_exists($classInCurrentNamespace)) {
                $class = static::normalizeClass($reflectionClass, $classInCurrentNamespace);
            } else {
                $classText = file_get_contents($reflectionClass->getFileName());
                preg_match(sprintf('/use (([\w_\\\\])+%s)/', $typeClass), $classText, $matches);

                $class = $matches[1] ?? null;
            }
        }

        $isClass = $class && class_exists($class);
        static::$classes[$typeClass] = $isClass ? $class : null;

        return static::$classes[$typeClass];
    }
}
