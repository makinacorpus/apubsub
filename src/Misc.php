<?php

namespace MakinaCorpus\APubSub;

final class Misc
{
    /**
     * SQL date time.
     */
    const SQL_DATETIME = 'Y-m-d H:i:s';

    /**
     * Tell if the given array has only numeric keys
     *
     * @param array $array
     */
    final static public function isIndexed(array $array)
    {
        // Best answer ever to that question:
        // http://stackoverflow.com/a/5969617/552405
        for (reset($array); is_int(key($array)); next($array));
        return is_null(key($array));
    }

    final static public function toArray($values)
    {
        if ($values instanceof \Traversable) {
            return iterator_to_array($values);
        }
        if (!is_array($values)) {
            return [$values];
        }
        return $values;
    }

    final static public function toIterable($values)
    {
        if (self::isIterable($values)) {
            return $values;
        }
        return [$values];
    }

    final static public function isIterable($values)
    {
        return is_array($values) || $values instanceof \Traversable;
    }
}
