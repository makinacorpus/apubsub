<?php

namespace APubSub\Error;

use \Exception as SplException;

class ChannelAlreadyExistsException extends SplException implements Exception
{
}
