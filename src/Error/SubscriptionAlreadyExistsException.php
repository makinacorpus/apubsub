<?php

namespace MakinaCorpus\APubSub\Error;

use \Exception as SplException;

class SubscriptionAlreadyExistsException extends SplException implements Exception
{
}
