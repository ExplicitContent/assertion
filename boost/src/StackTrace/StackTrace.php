<?php

namespace ExplicitContent\Boost\StackTrace;

use RuntimeException;
use Throwable;

/**
 * @internal
 */
final class StackTrace
{
    private $lines;

    public static function fromThrowable(Throwable $e)
    {
        return self::fromArray($e->getTrace());
    }

    public static function current(): self
    {
        return self::fromArray(debug_backtrace());
    }

    private static function fromArray(array $trace): self
    {
        return new self(
            array_map(
                function (array $line): StackTraceLine {
                    return new StackTraceLine(
                        $line['function'],
                        $line['file'] ?? null,
                        $line['line'] ?? null,
                        $line['class'] ?? null,
                        $line['type'] ?? null,
                        $line['args'] ?? null
                    );
                },
                $trace
            )
        );
    }

    /**
     * @param StackTraceLine[] $lines
     */
    private function __construct(array $lines)
    {
        $this->lines = $lines;
    }

    public function findTopLineWithClass(string $class): StackTraceLine
    {
        foreach ($this->lines as $line) {
            if ($line->getClass() === $class) {
                return $line;
            }
        }

        throw new RuntimeException('Stack trace does not contain appropriate call.');
    }
}
