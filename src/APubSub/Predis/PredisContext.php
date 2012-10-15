<?php

namespace APubSub\Predis;

use Predis\Client;

class PredisContext
{
    /**
     * Channel based key prefix
     */
    const KEY_PREFIX_CHAN = 'c:';

    /**
     * Subscription based key prefix
     */
    const KEY_PREFIX_MSG = 'm:';

    /**
     * Subscription based key prefix
     */
    const KEY_PREFIX_SUB = 's:';

    /**
     * Sequences key prefix
     */
    const KEY_PREFIX_SEQ = 'seq:';

    /**
     * @var string
     */
    protected $keyPrefix = 'apb:';

    /**
     * @var \Predis\Client
     */
    protected $client;

    public function __construct(Client $client = null, array $options = null)
    {
        if (null !== $client) {
            $this->client = $client;
        }

        if (null !== $options) {
            $this->parseOptions($options);
        }
    }

    /**
     * Parse options and populate internal values
     *
     * @param array $options Options
     */
    public function parseOptions(array $options)
    {
        if (null === $this->client) {
            // Parse client options
            $connectionInfo = array();
            // FIXME: Missing database, sharding, clustering, etc...
            foreach (array('scheme', 'host', 'port') as $key) {
                if (isset($options[$key])) {
                    $connectionInfo[$key] = $options[$key];
                }
            }

            // Attempt a default connection weither or not there is information
            // available
            $this->client = new Client($connectionInfo);
        }

        if (isset($options['keyprefix'])) {
            $this->keyPrefix = $options['keyprefix'];
        }
    }

    /**
     * Get key name
     *
     * @param string $name Original key name
     *
     * @return string      Prefixed key name
     */
    public function getKeyName($name)
    {
        return $this->keyPrefix . $name;
    }

    /**
     * Get next sequence id
     *
     * @param string $name
     */
    public function getNextId($name)
    {
        $seqKey  = $this->getKeyName(self::KEY_PREFIX_SEQ . $name);
        $retries = 5;

        do {
            $this->predisClient->watch($seqKey);
            $this->predisClient->multi();
            $value = $this->predisClient->incr($seqKey);
            $replies = $this->predisClient->exec();

        } while (!$replies[0] && 0 < $retries--);

        return $seqKey;
    }
}
