<?php

namespace ExplicitContent\Boost\Strings
{
    use Closure;

    function fstr(string $message, array $params): string
    {
        /**
         * So far it is simple PSR-3-like interpolation algorithm.
         *
         * TODO: need to replace it with decent formatter.
         */
        $map = [];

        foreach ($params as $key => $val) {
            $map['{' . $key . '}'] = is_scalar($val) ? $val : BoostedString::dump($val);
        }

        return strtr($message, $map);
    }

    function stringify($value, int $depth = 5): string
    {
        if (is_string($value)) {
            return $value;
        }

        return BoostedString::dump($value, $depth);
    }

    function dump($value, int $depth = 5): string
    {
        return BoostedString::dump($value, $depth);
    }

    /**
     * @internal
     */
    function array2string(array $array, ?int $elements, bool $forceList, int $depth): string
    {
        $list = (array_values($array) === $array) || $forceList;

        if ($elements !== null) {
            $reduced = count($array) > $elements;

            if ($reduced) {
                $array = array_slice($array, 0, $elements);
            }

        } else {
            $reduced = false;
        }

        if ($list) {
            $r = array_map(
                function ($value) use ($depth) {
                    return dump($value, $depth);
                }, $array
            );
        } else {
            $r = array_map(
                function ($key, $value) use ($depth) {
                    return dump($key) . ': ' . dump($value, $depth);
                },
                array_keys($array),
                array_values($array)
            );
        }

        if ($reduced) {
            $r[] = '...';
        }

        if ($list) {
            return '[' . implode(', ', $r) . ']';
        } else {
            return '{' . implode(', ', $r) . '}';
        }
    }

    function array2list(array $array)
    {
        return implode(', ', array_map(function ($value) { return dump($value); }, $array));
    }
}
