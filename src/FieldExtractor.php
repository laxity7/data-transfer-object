<?php

namespace Laxity7;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class FieldExtractor
{
    /**
     * @var array<string, string[]> Field cache
     */
    private static $cache = [];

    public static function getFields(object $object): array
    {
        $className = $object::class;
        if (isset(self::$cache[$className])) {
            return self::$cache[$className];
        }

        $fields = [];
        $class = new ReflectionClass($object);
        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            $name = $method->getName();
            if ($method->isStatic() || !str_starts_with($name, 'get')) {
                continue;
            }

            $fields[] = strtolower($name[3]) . substr($name, 4);
        }

        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED) as $property) {
            $name = $property->getName();
            if ($property->isStatic() || str_starts_with($name, '_')) {
                continue;
            }

            $fields[] = $name;
        }

        self::$cache[$className] = array_unique($fields);

        return self::$cache[$className];
    }
}
