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
                if (!str_contains(strtolower($comments), 'dto')) {
                    continue;
                }
                preg_match('/@var ((?:[\w?|\\\\,]+(?:\[])?)+)/', $comments, $matches);
                $definition = trim($matches[1] ?? '');
                $type = rtrim($definition, '[]');
                $isArray = $definition !== $type;
            }

            $type = static::normalizeClass($dto, $type);
            if (!$type) {
                continue;
            }

            if (!$isArray) {
                $attributes[$name] = new $type($value);
            } else {
                $attributes[$name] = [];
                foreach ($value as $key => $item) {
                    $attributes[$name][$key] = is_array($item) ? new $type($item) : $item;
                }
            }
        }

        return $attributes;
    }

    /**
     * Normalize class namespace
     *
     * @param BaseDTO $dto
     * @param string  $typeClass
     * @return string|null Class namespace
     */
    protected static function normalizeClass(BaseDTO $dto, string $typeClass): ?string
    {
        if (isset(static::$classes[$typeClass])) {
            return static::$classes[$typeClass];
        }

        $class = $typeClass;
        if (!str_contains($class, '\\')) {
            $reflectionClass = new ReflectionClass($dto);

            $classInCurrentNamespace = $reflectionClass->getNamespaceName() . '\\' . $typeClass;
            if (class_exists($classInCurrentNamespace)) {
                $class = static::normalizeClass($dto, $classInCurrentNamespace);
            } else {
                $classText = file_get_contents($reflectionClass->getFileName());
                preg_match(sprintf('/use (([\w_\\\\])+%s)/', $typeClass), $classText, $matches);

                $class = $matches[1] ?? null;
            }
        }

        $isDtoClass = $class && class_exists($class) && (new ReflectionClass($class))->isSubclassOf(BaseDTO::class);
        static::$classes[$typeClass] = $isDtoClass ? $class : null;

        return static::$classes[$typeClass];
    }
}