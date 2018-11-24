<?php

namespace ExplicitContent\Boost\StackTrace;

use ExplicitContent\Assertion\Assertion;
use LogicException;
use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

final class StackTraceLine
{
    private $function;
    private $file;
    private $line;
    private $class;
    private $type;
    private $args;

    public function __construct(string $function, ?string $file, ?int $line, ?string $class, ?string $type, ?array $args)
    {
        $this->function = $function;
        $this->file = $file;
        $this->line = $line;
        $this->class = $class;
        $this->type = $type;
        $this->args = $args;
    }

    public function getFunction(): string
    {
        return $this->function;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function getLine(): ?int
    {
        return $this->line;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getArgs(): ?array
    {
        return $this->args;
    }

    public function parseAssertionCode(): string
    {
        if ($this->file == null || $this->line === null) {
            throw new LogicException(
                sprintf('Impossible to parse call code of %s function: file or line were not specified.', $this->function)
            );
        }

        $line = $this->line;

        $callVisitor = new class ($line) extends NodeVisitorAbstract {
            public $ast;
            private $line;

            public function __construct(int $line)
            {
                $this->line = $line;
            }

            public function enterNode(Node $node)
            {
                if ($node->getStartLine() !== $this->line) {
                    return null;
                }

                if ($node instanceof Node\Expr\StaticCall) {
                    if ($node->class->toString() === Assertion::class) {
                        $node->class = $node->class->getAttribute('originalName');
                        $this->ast = $node;
                    }
                }
            }
        };

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7, new Emulative());
        $nodes = $parser->parse((string)file_get_contents($this->file));

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver(null, ['preserveOriginalNames' => true]));
        $traverser->addVisitor($callVisitor);
        $traverser->traverse($nodes);

        $prettyPrinter = new Standard;
        return $prettyPrinter->prettyPrint([$callVisitor->ast]);
    }
}
