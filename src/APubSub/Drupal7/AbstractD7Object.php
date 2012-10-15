<?php

namespace APubSub\Drupal7;

/**
 * Base implementation for Drupal 7 objects
 */
class AbstractD7Object implements D7ObjectInterface
{
    /**
     * @var \DatabaseConnection
     */
    private $dbConnection;

    /**
     * Set database connection
     *
     * @param \DatabaseConnection $dbConnection Database connection
     */
    public function setDatabaseConnection(\DatabaseConnection $dbConnection)
    {
        if (null !== $this->dbConnection) {
            throw new \LogicException("Database connection cannot be unset");
        }

        $this->dbConnection = $dbConnection;
    }

    /**
     * Get database connection
     *
     * @return \DatabaseConnection Database connection
    */
    public function getDatabaseConnection()
    {
        if (null === $this->dbConnection) {
            throw new \LogicException("Database connection is not set");
        }

        return $this->dbConnection;
    }
}
