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
        for (reset($array); is_int(key($array)); next($array));
            if (!is_null(key($array))) {
                return false;
            }
        }
        return true;
    }
}
