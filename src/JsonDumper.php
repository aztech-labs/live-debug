<?php

namespace Aztech\LiveDebug;

use Symfony\Component\VarDumper\Cloner\Cursor;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\DumperInterface;
use Symfony\Component\VarDumper\Cloner\scalar;
use Symfony\Component\VarDumper\Dumper\DataDumperInterface;

/**
 * CliDumper dumps variables for command line output.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class JsonDumper implements DataDumperInterface, DumperInterface
{

    private $stack;

    private $parentType;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->reset();
    }

    private function reset()
    {
        $this->stack = new \SplStack();
        $this->parentType = new \SplStack();
        $this->parentType->push(false);
    }

    protected function getKey(Cursor $cursor)
    {
        if ($cursor->hashKey !== null) {
            return $this->getKeyName($cursor, $cursor->hashKey);
        }

        return null;
    }

    protected function getKeyName(Cursor $cursor)
    {
        $key = $cursor->hashKey;

        switch ($cursor->hashType) {
            default:
            case Cursor::HASH_INDEXED:
            case Cursor::HASH_ASSOC:
                if (is_int($key)) {
                    return (int) $key;
                }
                return $key;
            case Cursor::HASH_RESOURCE:
            case Cursor::HASH_OBJECT:
                if (0 < strpos($key, "\0", 1)) {
                    list(, $key) = explode("\0", substr($key, 1), 2);
                }

                return $key;
        }
    }

    protected function getKeyScope(Cursor $cursor, $key = null)
    {
        $key = $key ?: $cursor->hashKey;

        switch ($cursor->hashType) {
            case Cursor::HASH_RESOURCE:
            case Cursor::HASH_OBJECT:
                if (0 < strpos($key, "\0", 1)) {
                    $key = explode("\0", substr($key, 1), 2);

                    switch ($key[0]) {
                        case '~':
                            return $this->getKeyScope($cursor, $key[1]);
                        case '+':
                            return 'public';
                        case '*':
                            return 'protected';
                        default:
                            return 'private';
                    }
                }

                return 'private';
        }

        return null;
    }

    protected function push(Cursor $cursor, $data)
    {
        $key = $this->getKeyName($cursor);

        if (! $this->parentType->isEmpty() && $this->parentType->top() == true) {
            $data = [
                'visibility' => $this->getKeyScope($cursor),
                'value' => $data
            ];
        }

        $key = $this->getKeyName($cursor, $key);

        if (! $this->stack->isEmpty()) {
            $top = $this->stack->pop();

            if ($key !== null) {
                $top[$key] = $data;
            }
            else {
                $top[] = $data;
            }
        }
        else {
            $top = [];

            if ($key === null) {
                $top = $data;
            }
        }

        $this->stack->push($top);
    }

    public function dump(Data $data)
    {
        $this->reset();
        $data->dump($this);

        return $this->stack->bottom();
    }

    /**
     * Dumps a scalar value.
     *
     * @param Cursor $cursor The Cursor position in the dump.
     * @param string $type The PHP type of the value being dumped.
     * @param scalar $value The scalar value being dumped.
     */
    public function dumpScalar(Cursor $cursor, $type, $value)
    {
        $this->push($cursor, [ 'classifier' => 'scalar', 'type' => $type, 'value' => $value ]);
    }

    /**
     * Dumps a string.
     *
     * @param Cursor $cursor The Cursor position in the dump.
     * @param string $str The string being dumped.
     * @param bool $bin Whether $str is UTF-8 or binary encoded.
     * @param int $cut The number of characters $str has been cut by.
     */
    public function dumpString(Cursor $cursor, $str, $bin, $cut)
    {
        $this->push($cursor,  [ 'classifier' => 'scalar', 'type' => 'string', 'value' => $str ]);
    }

    /**
     * Dumps while entering an hash.
     *
     * @param Cursor $cursor The Cursor position in the dump.
     * @param int $type A Cursor::HASH_* const for the type of hash.
     * @param string $class The object class, resource type or array count.
     * @param bool $hasChild When the dump of the hash has child item.
     */
    public function enterHash(Cursor $cursor, $type, $class, $hasChild)
    {
        $this->stack->push([  ]);
        $this->parentType->push($type == Cursor::HASH_OBJECT);
    }

    /**
     * Dumps while leaving an hash.
     *
     * @param Cursor $cursor The Cursor position in the dump.
     * @param int $type A Cursor::HASH_* const for the type of hash.
     * @param string $class The object class, resource type or array count.
     * @param bool $hasChild When the dump of the hash has child item.
     * @param int $cut The number of items the hash has been cut by.
     */
    public function leaveHash(Cursor $cursor, $type, $class, $hasChild, $cut)
    {
        $top = $this->stack->pop();

        if ($type == Cursor::HASH_OBJECT) {
            $top = [
                'classifier' => 'object',
                'type' => $class,
                'id' =>$cursor->softRefHandle,
                'properties' => $top
            ];

            if (! $hasChild) {
                unset($top['properties']);
            }
        }
        else {
            $top = [
                'classifier' => 'array',
                'type' => 'Array',
                'elements' => $top
            ];
        }

        $this->parentType->pop();
        $this->push($cursor, $top);
    }
}