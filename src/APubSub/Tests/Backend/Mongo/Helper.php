<?php

namespace APubSub\Tests\Backend\Mongo;

final class Helper
{
    /**
     * @var \Mongo
     */
    private static $cx;

    /**
     * Get testing mongo database
     *
     * @return \Mongo Mongo connection or null if none found
     */
    static public function getMongoConnection()
    {
        if (!class_exists('\Mongo')) {
            return null;
        }

        if (null === self::$cx) {
            if ($serverUrl = getenv('MONGO_URL')) {
                $cxUrl = $serverUrl . "/" . self::getDbName();

                self::$cx = new \Mongo($cxUrl);
            } else {
                return null;
            }
        }

        return self::$cx;
    }

    static public function getDbName()
    {
        return 'apubsub_test';
    }

    static public function cleanup()
    {
        self::getMongoConnection()->dropDB(self::getDbName());
    }
}
