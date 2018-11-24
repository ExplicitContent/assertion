# Assertion

Assertion is a library for low-level validation, allows to verify invariants and pre/post conditions.
Failed assertion must be considered as a bug in the code or (and) lack of validation on higher levels.
Failed assertion throws unchecked exception and, in general, should not be caught directly.

The library is inspired by [beberlei/assert](https://github.com/beberlei/assert) and similar ones.

## Status

So far this library is **not production-ready**. 

TODO:

- custom Assertion API generator (with copied assertions' code);
- procedural facade generator for native `assert(that()->notNull(...));` (?);
- more examples;
- replace `fstr()` with decent string formatter;
- ...

## Installation

```
composer require explicit-content/assertions "dev-master"
```

## Examples

This Assertion library tries to "extend" language features regarding contracts. The following list shows some possible ways of usage, but it doesn't cover them all.

### Protecting invariants of aggregate roots, value objects, etc.

Protecting invariants of your value objects, aggregate roots:

```php
final class MonopolyGame
{
    // ...
    
    public function rollDice(PlayerId $playerId, DiceResult $diceResult): void
    {
        Assertion::notNull($this->turn, 'Game is over.');
        Assertion::true($this->turn->currentPlayerId->equals($playerId));

        if ($this->turn->diceRolled !== null) {
            return;
        }

        // ...
    }
}
```

```php
final class Month
{
    private $month;

    private const JANUARY = 'January';
    private const FEBRUARY = 'February';
    private const MARCH = 'March';
    private const APRIL = 'April';
    // ...

    private const MAP = [
        self::JANUARY => 1,
        self::FEBRUARY => 2,
        self::MARCH => 3,
        self::APRIL => 4,
        // ...
    ];

    // ...

    public static function fromInt(int $month): self
    {
        Assertion::numeric($month)->between(1, 12, 'Month number must be in 1..12 range.');

        return new self(array_search($month, self::MAP));
    }

    public static function fromString(string $month): self
    {
        Assertion::array(array_keys(self::MAP))->contains(ucfirst(strtolower($month)));
        
        return new self($month);
    }

    private function __construct(string $month)
    {
        Assertion::array(array_keys(self::MAP))->contains($month);

        $this->month = $month;
    }
}
```

### Imitation of delegate types

There was [attempt to introduce interfaces](https://wiki.php.net/rfc/functional-interfaces) for functions, but it was declined.

Other languages like C# (`public delegate HttpRequest Modify (HttpRequest request);`), Scala (`f: HttpRequest => HttpRequest`) have this feature.

Using this library, one could imitate this feature in several ways. For example, this is imitation of delegate type that declares set of functions which modifies HTTP-request somehow, e.g.:

```php
final class HttpRequestModifier
{
    private $closure;

    public function __construct(Closure $closure)
    {
        Assertion::callable($closure)->respectsMethodSignature(self::class, 'modify');
        
        $this->closure = $closure;
    }
    
    public function modify(HttpRequest $request): HttpRequest
    {
        return call_user_func($this->closure, $request);
    }
}

$httpClient = $httpClient->withHttpRequestModifier(
    new HttpRequestModifier(
        function (HttpRequest $request): HttpRequest {
            return $request->withHeader('User-Agent', 'ExplicitContent');
        }
    )
);
```

Another possible way is to compare signatures of actual (passed) closure and sample one:

```php
final class HttpRequestModifier
{
    private $closure;

    public function __construct(Closure $closure)
    {
        Assertion::callable($closure)->respectsSignatureOfSample(
            function (HttpRequest $request): HttpRequest {
                return $request->withHeader('User-Agent', 'ExplicitContent');
            }
        );

        $this->closure = $closure;
    }

    public function modify(HttpRequest $request): HttpRequest
    {
        return call_user_func($this->closure, $request);
    }
}
```

In the last example you'd provide example for another developer and you'd decrease chances of surprises at the stage of calling `->modify()` method: it's always better to [fail fast](https://en.wikipedia.org/wiki/Fail-fast) from DX point of view.

Note: "unnamed" refers here to the fact that we can pass different function/method names to the constructor of `HttpRequestModifier`, `HttpRequestModifier` by itself acts like a type: type has a name, but method name acts like value in this context.

### Collections

The library allows to work with collection of objects as replacement for desired `IpAddress[]`:

```php
final class IpAddressCollection
{
    public function __construct(array $ips)
    {
        Assertion::array($ips)
            ->of(IpAddress::class)
            ->unique()

        // ...
    }
}
```

### Dot notated access to arrays

There is a support to access array with dot notation:

```php
final class LegacyApiResponse
{
    public static function fromArray(array $response)
    {
        Assertion::array($response)
            ->dotNotated()
            ->keysExist([
                'status',
                'data.user.id',
                'data.user.email'
            ]);
        
        return new self(
            $response['status'],
            $response['data']['user']['id'],
            $response['data']['user']['email']
        );
    }
    
    private function __construct(...)
    {
        // ...
    }
}
```

Sometimes it's useful to check response from remote API, for instance. Failing assertions would indicate that remote server broke backward-compatibility (or it would disclose your misconception of the API).

--------------

## Error messages

In case of trivial assertions like `Assertion::true(...)` you can omit second argument with message and the library will form message with assertion code by itself:

```
try {
    Assertion::true(42 === 43);
} catch (\Throwable $e) {
    // "Assertion::true(42 === 43) failed in <...> at line 42."
    echo $e->getMessage(), PHP_EOL;
}
```
