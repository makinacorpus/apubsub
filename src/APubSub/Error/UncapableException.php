<?php

namespace APubSub\Error;

use \Exception as SplException;

/**
 * Exception thrown by a backend when an optional not implemented method is
 * called upon any API object
 */
class UncapableException extends SplException implements Exception
{
}
