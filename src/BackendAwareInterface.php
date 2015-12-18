<?php

namespace MakinaCorpus\APubSub;

/**
 * All objects except backend will inherit from this
 */
interface BackendAwareInterface
{
    /**
     * Get backend
     *
     * @return BackendInterface
     */
    public function getBackend();
}
