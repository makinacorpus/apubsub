<?php

namespace APubSub\Error;

use \Exception as SplException;

class SubscriptionDoesNotExistException extends SplException implements Exception
{
}
