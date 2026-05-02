<?php

declare(strict_types=1);

namespace Mariusz\Logger\Tests\Unit;

use Mariusz\Logger\LogAnonymizer;
use PHPUnit\Framework\TestCase;

final class LogAnonymizerTest extends TestCase
{
    private LogAnonymizer $anonymizer;

    protected function setUp(): void
    {
        $this->anonymizer = new LogAnonymizer();
    }

    public function testShortValueBecomesOnlyAsterisks(): void
    {
        $result = $this->anonymizer->anonymize(['token' => 'ab']);
        $this->assertSame('****', $result['token']);
    }

    public function testExactlyFourCharsBecomesOnlyAsterisks(): void
    {
        $result = $this->anonymizer->anonymize(['password' => 'abcd']);
        $this->assertSame('****', $result['password']);
    }

    public function testLongerValueHasVisibleStartAndEnd(): void
    {
        $result = $this->anonymizer->anonymize(['pesel' => '12345678901']);
        $this->assertStringStartsWith('1', $result['pesel']);
        $this->assertStringEndsWith('1', $result['pesel']);
        $this->assertStringContainsString('****', $result['pesel']);
    }

    public function testEmailIsMasked(): void
    {
        $result = $this->anonymizer->anonymize(['email' => 'jan.kowalski@gmail.com']);
        $this->assertStringContainsString('****', $result['email']);
        $this->assertNotSame('jan.kowalski@gmail.com', $result['email']);
    }

    public function testNonSensitiveFieldIsUntouched(): void
    {
        $result = $this->anonymizer->anonymize(['username' => 'jankowalski']);
        $this->assertSame('jankowalski', $result['username']);
    }

    public function testFieldNameIsCaseInsensitive(): void
    {
        $result = $this->anonymizer->anonymize(['TOKEN' => 'supersecret123']);
        $this->assertStringContainsString('****', $result['TOKEN']);
    }

    public function testNestedArrayIsAnonymized(): void
    {
        $result = $this->anonymizer->anonymize([
            'user' => ['email' => 'test@example.com', 'name' => 'Jan'],
        ]);
        $this->assertStringContainsString('****', $result['user']['email']);
        $this->assertSame('Jan', $result['user']['name']);
    }

    public function testNonStringValueIsUntouched(): void
    {
        $result = $this->anonymizer->anonymize(['pesel' => 12345678901]);
        $this->assertSame(12345678901, $result['pesel']);
    }

    public function testEmptyContextReturnsEmpty(): void
    {
        $this->assertSame([], $this->anonymizer->anonymize([]));
    }
}
