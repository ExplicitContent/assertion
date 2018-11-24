<?php

namespace ExplicitContent\Boost\Callables;

use Closure;
use ExplicitContent\Assertion\Assertion;
use Reflection;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use function ExplicitContent\Boost\Strings\fstr;

/**
 * It allows to compare method signatures fast.
 * However, it's kinda fragile for the future PHP versions: this dump is for human beings, so format may be changed.
 * But I think it is worth it to take the risk.
 */
final class SignatureExporter
{
    public static function exportFromClassMethod(string $class, string $method): string
    {
        return self::export(new ReflectionMethod($class, $method));
    }

    public static function exportFromClosure(Closure $closure): string
    {
        return self::export(new ReflectionFunction($closure));
    }

    private static function export(ReflectionFunctionAbstract $function): string
    {
        $dump = Reflection::export($function, true);
        $lines = explode("\n", $dump);

        $signature = '';

        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/^(?<indentation>\s*)- Parameters \[(?<number>\d+)\]/', $lines[$i], $match)) {
                for ($j = 0; $j < $match['number'] + 2; $j++, $i++) {
                    $signature .= $lines[$i] . "\n";
                }

                if (strpos($lines[$i], $match['indentation'] . '- Return') === 0) {
                    $signature .= $lines[$i];
                    $i++;
                }

                if ($lines[$i] === '}') {
                    break;
                }

                Assertion::unreachable(fstr('Parser of signature failed, exported data: {dump}', [
                    'dump' => $dump
                ]));
            }
        }

        return (string)preg_replace('/\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', '$?', $signature);
    }
}
