<?php

namespace MakinaCorpus\APubSub;

/**
 * A message is the raw arbitrary information sent.
 */
interface MessageInterface extends BackendAwareInterface
{
    /**
     * Get internal message identifier
     *
     * @return scalar
     *   Identifier whose type depends on the backend implementation
     */
    public function getId();

    /**
     * Get message type
     *
     * @return string
     *   Message type or null if none set
     */
    public function getType();

    /**
     * Get message contents
     *
     * @return mixed
     *   Data set by the sender
     */
    public function getContents();

    /**
     * Get message level
     *
     * Message level is an arbitrary integer value which can have any purpose
     * in the upper business value. It doesn't alter the default behavior.
     *
     * @return int
     *   Arbitrary level set in queue
     */
    public function getLevel();

    /**
     * Get origin identifier
     *
     * Origin is an arbitrary scalar value given by the user of the API, it has
     * internally no meaning and will only be available for query purposes
     *
     * @return string
     */
    public function getOrigin();
}
