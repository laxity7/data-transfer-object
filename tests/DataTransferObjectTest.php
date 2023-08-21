<?php

/**
 * Created by Vlad Varlamov (laxity.ru) on 24.08.2022.
 */

namespace Laxity7\Test;

use Closure;
use Laxity7\DataTransferObject;
use Laxity7\Test\dtos\ChildDto;
use Laxity7\Test\dtos\foo\FooDto;
use Laxity7\Test\dtos\ReadonlyDto;
use Laxity7\Test\dtos\ReadWriteDto;
use Laxity7\Test\dtos\RootDto;
use Laxity7\UnknownPropertyException;
use PHPUnit\Framework\TestCase;
use Throwable;

class DataTransferObjectTest extends TestCase
{
    private const DATA = [
        'id' => 10,
        'firstName' => 'John',
        'lastName' => 'Doe',
        'patronymic' => 'jr.',
        'data' => [
            'foo' => 'bar'
        ],
        'child' => [
            'id' => 20
        ],
        'subChild' => null,
        'children' => [],
        'foo' => [],
        'fooBar' => [],
        'readonly' => [
            'bar' => 'baz',
            'foo' => 'gaz',
        ],
        'readonlyArr' => [],
    ];

    private const DATA_FULL = [
        'children' => [
            ['id' => 40],
            ['id' => 50],
        ],
        'foo' => [
            ['id' => 60],
            ['id' => 70],
        ],
        'fooBar' => [
            ['id' => 80],
            ['id' => 90],
        ],
        'readonly' => [
            'bar' => 'baz',
            'foo' => 'gaz',
        ],
        'readonlyArr' => [
            ['bar' => 'baz', 'foo' => 'gaz'],
            ['bar' => 'baz1', 'foo' => 'gaz1'],
        ],
    ];


    public function testTypeCast(): void
    {
        $subChild = new ChildDto(['id' => 30]);
        $children = self::DATA_FULL['children'];
        $foo = self::DATA_FULL['foo'];
        $fooBar = self::DATA_FULL['fooBar'];
        $readonlyArr = self::DATA_FULL['readonlyArr'];

        $dto = new RootDto(array_merge(self::DATA, compact('subChild', 'children', 'foo', 'fooBar', 'readonlyArr')));

        self::assertEquals(self::DATA['id'], $dto->id);
        self::assertEquals(self::DATA['firstName'], $dto->firstName);
        self::assertEquals(self::DATA['lastName'], $dto->lastName);
        self::assertEquals(self::DATA['data'], $dto->data);
        self::assertInstanceOf(ChildDto::class, $dto->child);
        self::assertEquals(self::DATA['child']['id'], $dto->child->id);
        self::assertInstanceOf(ReadonlyDto::class, $dto->readonly);
        self::assertEquals(self::DATA['readonly']['foo'], $dto->readonly->foo);
        self::assertEquals(self::DATA['readonly']['bar'], $dto->readonly->bar);
        self::assertEquals($subChild, $dto->subChild);
        self::assertIsArray($dto->children);

        self::assertIsArray($dto->children);
        $i = 0;
        foreach ($dto->children as $child) {
            self::assertInstanceOf(ChildDto::class, $child);
            self::assertEquals($children[$i++]['id'], $child->id);
        }
        self::assertIsArray($dto->foo);
        $i = 0;
        foreach ($dto->foo as $fooDto) {
            self::assertInstanceOf(FooDto::class, $fooDto);
            self::assertEquals($foo[$i++]['id'], $fooDto->id);
        }
        self::assertIsArray($dto->fooBar);
        $i = 0;
        foreach ($dto->fooBar as $fooBarDto) {
            self::assertInstanceOf(FooDto::class, $fooBarDto);
            self::assertEquals($fooBar[$i++]['id'], $fooBarDto->id);
        }
        $i = 0;
        foreach ($dto->readonlyArr as $readonlyDto) {
            self::assertInstanceOf(ReadonlyDto::class, $readonlyDto);
            self::assertEquals($readonlyArr[$i]['foo'], $readonlyDto->foo);
            self::assertEquals($readonlyArr[$i]['bar'], $readonlyDto->bar);
            $i++;
        }

        // checking the order in which variables are set
        $child = new ChildDto([
            'id' => 30,
            'name' => 'Joe'
        ]);
        self::assertEquals('JoeFoo', $child->name);

        // check set undefined field
        self::assertException(function () {
            $childFoo = new class(['id' => 30, 'foo' => 1]) extends DataTransferObject {
                protected int $id;

                protected function ignoreUndefinedFields(): bool
                {
                    return false;
                }
            };
        }, UnknownPropertyException::class);

        // check set undefined field
        $childFoo = new ChildDto(['id' => 30, 'foo' => 1]);
        self::assertInstanceOf(ChildDto::class, $childFoo);

        // check get undefined field
        self::assertException(function () {
            $childFoo = new ChildDto(['id' => 30]);
            $childFoo->foo;
        }, UnknownPropertyException::class);
    }

    public function testFields(): void
    {
        $dto = new RootDto(self::DATA);

        self::assertEqualsCanonicalizing(array_keys(self::DATA), $dto->fields());
    }

    public function testToArray(): void
    {
        $dto = new RootDto(self::DATA);
        $attributes = $dto->toArray();

        self::assertEqualsCanonicalizing(array_keys(self::DATA), array_keys($attributes));

        self::assertEquals(self::DATA['id'], $attributes['id']);
        self::assertEquals(self::DATA['firstName'], $attributes['firstName']);
        self::assertEquals(self::DATA['lastName'], $attributes['lastName']);
        self::assertEquals(self::DATA['data'], $attributes['data']);
        self::assertEquals(self::DATA['child']['id'], $attributes['child']->id);
        self::assertEquals(null, $attributes['child']->name);
    }

    public function testJsonSerialize(): void
    {
        $data = array_merge(self::DATA, self::DATA_FULL);
        $dto = new RootDto($data);

        $data['child']['name'] = null;
        $data['children'][0]['name'] = null;
        $data['children'][1]['name'] = null;
        self::assertJsonStringEqualsJsonString(json_encode($data), json_encode($dto));

        $newDto = new RootDto(json_decode(json_encode($dto), true));
        self::assertJsonStringEqualsJsonString(json_encode($newDto), json_encode($dto));
    }

    public function testUpdate(): void
    {
        $data = [
            'id' => 1,
            'firstname' => 'Joe',
            'lastname' => 'Doe',
        ];
        $dto = new ReadWriteDto($data);

        $dto->firstname = 'foo';
        self::assertEquals('foo', $dto->firstname);

        self::assertException(fn() => $dto->id = 0, UnknownPropertyException::class);
        self::assertEquals($data['id'], $dto->id);

        self::assertException(fn() => $dto->lastname = 'bar', UnknownPropertyException::class);
        self::assertEquals($data['lastname'] . ' jr.', $dto->lastname);
    }

    /**
     * Asserts that a function will return an exception.
     *
     * @param Closure $function
     * @param string $exceptionClassName
     * @param string $message
     */
    private static function assertException(
        Closure $function,
        string $exceptionClassName,
        string $exceptionMessage = '',
        string $message = ''
    ): void {
        $caught = false;
        try {
            $function();
        } catch (Throwable $e) {
            $caught = true;
            if ($e instanceof $exceptionClassName) {
                self::assertTrue(true, $message);
                if (!empty($exceptionMessage)) {
                    self::assertStringContainsString($exceptionMessage, $e->getMessage(), $message);
                }
            } else {
                self::assertInstanceOf($exceptionClassName, get_class($e), $message);
            }
        } finally {
            self::assertTrue(
                $caught,
                sprintf('Exception %s was expected to be thrown, but it wasn\'t', $exceptionClassName)
            );
        }
    }
}
