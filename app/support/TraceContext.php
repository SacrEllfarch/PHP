<?php

declare(strict_types=1);

namespace app\support;

class TraceContext
{
    private static ?string $traceId = null;

    public static function set(string $traceId): void
    {
        self::$traceId = $traceId;
    }

    public static function get(): string
    {
        if (self::$traceId === null) {
            self::$traceId = self::generate();
        }

        return self::$traceId;
    }

    public static function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}
