<?php

namespace APubSub;

final class Misc
{
    /**
     * Tell if the given array has only numeric keys
     *
     * @param array $array
     */
    static public function isIndexed(array $array)
    {
        // Best answer ever to that question:
        // http://stackoverflow.com/a/5969617/552405
        for (reset($array); is_int(key($array)); next($array));
        return is_null(key($array));
    }
}
