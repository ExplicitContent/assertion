<?php

namespace ExplicitContent\Assertion;

use ExplicitContent\Assertion\Exceptions\AssertionContainsError;
use ExplicitContent\Assertion\Exceptions\AssertionFailed;
use ExplicitContent\Boost\ErrorHandling\ErrorSandbox;
use function ExplicitContent\Boost\Strings\array2list;
use function ExplicitContent\Boost\Strings\fstr;

/**
 * @internal
 */
final class StringAssertions
{
    private const SUPPORTED_ENCODINGS = ['utf-8', '8bit'];

    private $subject;
    private $encoding;

    public function __construct(string $subject, string $encoding)
    {
        if (in_array($this->subject, self::SUPPORTED_ENCODINGS, true)) {
            throw new AssertionContainsError(
                fstr('Supporting encodings are: {list}.', ['list' => array2list(self::SUPPORTED_ENCODINGS)])
            );
        }

        $this->subject = $subject;
        $this->encoding = $encoding;
    }

    public function startsWith(string $prefix, string $message = '{subject} does not start with "{prefix}".'): self
    {
        if (mb_substr($this->subject, 0, (int)mb_strlen($prefix, $this->encoding), $this->encoding) === $prefix) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->subject, 'prefix' => $prefix]);
    }

    public function endsWith(string $suffix, string $message = '{subject} does not end with "{suffix}".'): self
    {
        if (mb_substr($this->subject, -mb_strlen($suffix, $this->encoding), null, $this->encoding) === $suffix) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->subject, 'suffix' => $suffix]);
    }

    public function lengthExactly(int $expected, string $message = 'Expected {expected} chars, the actual string contains {actual}.'): self
    {
        if (mb_strlen($this->subject, $this->encoding) === $expected) {
            return $this;
        }

        throw new AssertionFailed($message, ['actual' => strlen($this->subject), 'expected' => $expected]);
    }

    public function lengthMin(int $min, string $message = 'Expected at least {min} chars, the actual string contains {actual}.'): self
    {
        if (mb_strlen($this->subject, $this->encoding) >= $min) {
            return $this;
        }

        throw new AssertionFailed($message, ['actual' => strlen($this->subject), 'min' => $min]);
    }

    public function lengthMax(int $max, string $message = 'Expected at least {max} chars, the actual string contains {actual}.'): self
    {
        if (mb_strlen($this->subject, $this->encoding) >= $max) {
            return $this;
        }

        throw new AssertionFailed($message, ['actual' => strlen($this->subject), 'max' => $max]);
    }

    public function in(array $list, string $message = 'Value "{subject}" is not in array {list}.'): self
    {
        if (in_array($this->subject, $list, true)) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->subject, 'list' => $list]);
    }

    public function notIn(array $list, string $message = 'Value "{subject}" in the list: {list}.'): self
    {
        if (!in_array($this->subject, $list, true)) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->subject, 'list' => $list]);
    }

    public function matchesRegEx(string $pattern, string $message = '"{subject}" does not match PCRE pattern "{pattern}".'): self
    {
        $result = ErrorSandbox::invoke(function () use ($pattern) {
            return preg_match($pattern, $this->subject);
        });

        if ($result->getReturnValue() === 1) {
            return $this;
        }

        if ($result->hasErrors()) {
            throw new AssertionContainsError(
                fstr(
                    'RegEx "{pattern}" failed with error "{error}" and code "{code}".',
                    ['pattern' => $pattern, 'code' => preg_last_error(), 'error' => $result->getLastError()]
                )
            );
        }

        if (preg_last_error() !== \PREG_NO_ERROR) {
            throw new AssertionContainsError(
                fstr(
                    'RegEx "{pattern}" failed with code "{code}".',
                    ['pattern' => $pattern, 'code' => preg_last_error()]
                )
            );
        }

        throw new AssertionFailed($message, ['subject' => $this->subject, 'pattern' => $pattern]);
    }

    public function notBlank(string $message = 'Value is empty.'): self
    {
        if (strlen($this->subject) > 0) {
            return $this;
        }

        throw new AssertionFailed($message, []);
    }

    public function containsNot(string $substring, string $message = 'String "{subject}" contains "{substring}".'): self
    {
        if (strpos($this->subject, $substring) === false) {
            return $this;
        }

        throw new AssertionFailed($message, ['subject' => $this->subject, 'substring' => $substring]);
    }
}
