<?php

declare(strict_types=1);

namespace Mariusz\Logger\Tests\Unit;

use Mariusz\Logger\LogContextSerializer;
use PHPUnit\Framework\TestCase;

final class LogContextSerializerTest extends TestCase
{
    private LogContextSerializer $s;

    protected function setUp(): void
    {
        $this->s = new LogContextSerializer();
    }

    public function testScalarsPassThrough(): void
    {
        $result = $this->s->serialize(['a' => 1, 'b' => 'str', 'c' => true, 'd' => null, 'e' => 3.14]);
        $this->assertSame(['a' => 1, 'b' => 'str', 'c' => true, 'd' => null, 'e' => 3.14], $result);
    }

    public function testNestedArrayIsRecursive(): void
    {
        $result = $this->s->serialize(['nested' => ['x' => 1]]);
        $this->assertSame(['nested' => ['x' => 1]], $result);
    }

    public function testThrowableIsSerialized(): void
    {
        $e      = new \RuntimeException('boom', 42);
        $result = $this->s->serialize(['error' => $e]);

        $this->assertSame('RuntimeException', $result['error']['class']);
        $this->assertSame('boom', $result['error']['message']);
        $this->assertSame(42, $result['error']['code']);
        $this->assertStringContainsString(':', $result['error']['file']);
    }

    public function testThrowableWithPreviousIsNested(): void
    {
        $prev   = new \InvalidArgumentException('cause');
        $e      = new \RuntimeException('outer', 0, $prev);
        $result = $this->s->serialize(['error' => $e]);

        $this->assertSame('InvalidArgumentException', $result['error']['previous']['class']);
    }

    public function testResourceIsSerializedAsString(): void
    {
        $res    = fopen('php://memory', 'r');
        $result = $this->s->serialize(['res' => $res]);
        fclose($res);

        $this->assertStringContainsString('resource', $result['res']);
    }

    public function testJsonSerializableObject(): void
    {
        $obj = new class implements \JsonSerializable {
            public function jsonSerialize(): mixed { return ['key' => 'val']; }
        };

        $result = $this->s->serialize(['obj' => $obj]);
        $this->assertSame(['key' => 'val'], $result['obj']);
    }

    public function testObjectWithToArray(): void
    {
        $obj = new class {
            public function toArray(): array { return ['foo' => 'bar']; }
        };

        $result = $this->s->serialize(['obj' => $obj]);
        $this->assertSame(['foo' => 'bar'], $result['obj']);
    }

    public function testObjectWithToString(): void
    {
        $obj = new class {
            public function __toString(): string { return 'stringified'; }
        };

        $result = $this->s->serialize(['obj' => $obj]);
        $this->assertSame('stringified', $result['obj']);
    }

    public function testGenericObjectWithPublicProps(): void
    {
        $obj       = new \stdClass();
        $obj->name = 'test';
        $obj->val  = 99;

        $result = $this->s->serialize(['obj' => $obj]);
        $this->assertSame('stdClass', $result['obj']['class']);
        $this->assertSame('test', $result['obj']['name']);
        $this->assertSame(99, $result['obj']['val']);
    }

    public function testGenericObjectWithNoPropsReturnsClassName(): void
    {
        $obj    = new \stdClass();
        $result = $this->s->serialize(['obj' => $obj]);
        $this->assertSame('stdClass', $result['obj']);
    }
}
