<?php

namespace ExplicitContent\Boost\ErrorHandling;

use Closure;

/**
 * @internal
 */
final class ErrorSandbox
{
    public static function invoke(Closure $f): SandboxInvocationResult
    {
        $errors = [];

        set_error_handler(function ($type, string $msg) use (&$errors) {
            $errors[] = $msg;
        });

        try {
            return new SandboxInvocationResult($f(), $errors);
        } finally {
            restore_error_handler();
        }
    }
}
