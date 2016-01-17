<?php

namespace MakinaCorpus\APubSub\Backend;

use MakinaCorpus\APubSub\BackendInterface;

trait BackendAwareTrait
{
    /**
     * @var BackendInterface
     */
    protected $backend;

    /**
     * Set backend
     *
     * @param BackendInterface $backend
     *
     * @return $this
     *
     * @throws \LogicException
     */
    final public function setBackend(BackendInterface $backend)
    {
        if ($this->backend) {
            if ($this->backend === $backend) {
                trigger_error("setBackend() called twice");
            } else {
                throw new \LogicException("Cannot change component backend");
            }
        }
        $this->backend = $backend;

        return $this;
    }
}
