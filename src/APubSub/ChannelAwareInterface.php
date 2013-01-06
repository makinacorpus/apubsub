<?php

namespace APubSub;

interface ChannelAwareInterface
{
    /**
     * Get channel identifier
     *
     * Use this method rather than getChannel() whenever possible, in order to
     * avoid backends lookup in most implementations
     *
     * @return string
     */
    public function getChannelId();

    /**
     * Get channel
     *
     * @return \APubSub\ChannelInterface
     */
    public function getChannel();
}
