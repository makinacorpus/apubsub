<?php

namespace APubSub\Error;

use \Exception as SplException;

class ChannelDoesNotExistException extends SplException implements Exception
{
}
