<?php

namespace APubSub\Notification\Registry;

use APubSub\Notification\ChanType\NullChanType;

/**
 * Channel type registry
 */
class ChanTypeRegistry extends AbstractRegistry
{
    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\Registry\AbstractRegistry::createNullInstance()
     */
    protected function createNullInstance()
    {
        return new NullChanType();
    }

    /**
     * (non-PHPdoc)
     * @see \APubSub\Notification\Registry\AbstractRegistry::getInstanceFromData()
     */
    protected function getInstanceFromData($type, $data)
    {
        $class       = null;
        $description = null;

        if (is_array($data)) {
            $class       = $data['class'];
            $description = $data['description'];
        } else if (is_string($data)) {
            $class       = $data;
            $description = $type;
        } else {
            throw new \InvalidArgumentException(sprintf(
                "Invalid data given for type '%s' does not exist", $type));
        }

        if (!class_exists($class)) {
            throw new \LogicException(sprintf(
                "Class '%s' does not exist for type '%s'", $class, $type));
        }

        return new $class($type, $description, $data['visible']);
    }
}
