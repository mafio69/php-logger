<?php

declare(strict_types=1);

namespace Mariusz\Logger\Dto;

use Psr\Log\LogLevel;

final class LoggerConfigDto
{
    public function __construct(
        public readonly string $minLevel = LogLevel::WARNING,
        public readonly string $dateFormat = 'Y-m-d H:i:s',
        public readonly string $timezone = '',
        public readonly bool $stderrEnabled = true,
        public readonly bool $stderrSkipInTest = true,
    ) {
    }
}
