<?php

declare(strict_types=1);

namespace Conia\Boiler;

use \ArrayAccess;
use \Iterator;
use \Countable;
use \ErrorException;
use \InvalidArgumentException;
use \ValueError;


class ArrayValue implements ArrayAccess, Iterator, Countable, ValueInterface
{
    private int $position;
    private array $keys;

    public function __construct(private array $array)
    {
        $this->array = $array;
        $this->keys = array_keys($array);
        $this->position = 0;
    }

    public function unwrap(): array
    {
        return $this->array;
    }

    function rewind(): void
    {
        $this->position = 0;
    }

    function current(): mixed
    {
        return Wrapper::wrap($this->array[$this->key()]);
    }

    function key(): mixed
    {
        return $this->keys[$this->position];
    }

    function next(): void
    {
        ++$this->position;
    }

    function valid(): bool
    {
        return isset($this->keys[$this->position]);
    }

    public function offsetExists(mixed $offset): bool
    {
        // isset is significantly faster than array_key_exists but
        // returns false when the value exists but is null.
        return isset($this->array[$offset]) || array_key_exists($offset, $this->array);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if ($this->offsetExists($offset)) {
            return Wrapper::wrap($this->array[$offset]);
        } else {
            if (is_numeric($offset)) {
                $key = (string)$offset;
            } else {
                $key = "'$offset'";
            }

            throw new ErrorException("Undefined array key $key");
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset) {
            $this->array[$offset] = $value;
        } else {
            $this->array[] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->array[$offset]);
    }

    public function count(): int
    {
        return count($this->array);
    }

    public function exists(mixed $key): bool
    {
        return array_key_exists($key, $this->array);
    }

    public function merge(array|self $array): self
    {
        return new self(array_merge(
            $this->array,
            $array instanceof self ? $array->unwrap() : $array
        ));
    }

    public function map(callable $callable): self
    {
        return new self(array_map($callable, $this->array));
    }

    public function filter(callable $callable): self
    {
        return new self(array_filter($this->array, $callable));
    }

    public function reduce(callable $callable, mixed $initial = null): mixed
    {
        return Wrapper::wrap(array_reduce($this->array, $callable, $initial));
    }

    protected function sort(array $array, string $mode): self
    {
        match ($mode) {
            '' => sort($array),
            'ar' => arsort($array),
            'a' => asort($array),
            'kr' => krsort($array),
            'k' => ksort($array),
            'r' => rsort($array),
            default => throw new InvalidArgumentException("Sort mode '$mode' not supported"),
        };

        return new self($array);
    }

    protected function usort(array $array, string $mode, callable $callable): self
    {
        match ($mode) {
            'ua' => uasort($array, $callable),
            'u' => usort($array, $callable),
            default => throw new InvalidArgumentException("Sort mode '$mode' not supported"),
        };

        return new self($array);
    }

    public function sorted(string $mode = '', ?callable $callable = null): self
    {
        $mode = strtolower(trim($mode));

        if (str_starts_with($mode, 'u')) {
            if (empty($callable)) {
                throw new ValueError('No callable provided for user defined sorting');
            }

            return $this->usort($this->array, $mode, $callable);
        }

        return $this->sort($this->array, $mode);
    }
}
