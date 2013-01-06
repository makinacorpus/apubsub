<?php

namespace APubSub\Error;

use \Exception as SplException;

class SubscriptionAlreadyExistsException extends SplException implements Exception
{
}
