<?php

namespace APubSub\Error;

use \Exception as SplException;

class MessageDoesNotExistException extends SplException implements Exception
{
}
