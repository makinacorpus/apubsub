<?php

namespace APubSub\Drupal7;

/**
 * Common interface for all Drupal 7 based objects
 */
interface D7ObjectInterface
{
    /**
     * Set database connection
     *
     * @param \DatabaseConnection $dbConnection Database connection
     */
    public function setDatabaseConnection(\DatabaseConnection $dbConnection);

    /**
     * Get database connection
     *
     * @return \DatabaseConnection Database connection
     */
    public function getDatabaseConnection();
}
