<?php

namespace APubSub\Tests\Drupal;

class DrupalHelper
{
    /**
     * Is a Drupal instance bootstrapped
     *
     * @var bool
     */
    static private $bootstrapped = false;

    /**
     * Drupal version major which is bootstrapped
     *
     * @var int
     */
    static private $bootstrappedVersionMajor;

    /**
     * A database connection object from Drupal
     *
     * @var mixed
     */
    static private $databaseConnection;

    /**
     * Find if a Drupal instance is configured for testing and bootstrap it if
     * found.
     *
     * @return mixed The Drupal database connection object whose type depends
     *               on version major
     */
    static public function findDrupalDatabaseConnection($versionMajor)
    {
        if (self::$bootstrapped) {
            if (self::$bootstrappedVersionMajor === $versionMajor) {
                return self::$databaseConnection;
            } else {
                return null;
            }
        } else {
            $variableName = 'DRUPAL_PATH_' . $versionMajor;

            if (($path = getenv($variableName)) &&
                is_dir($path) &&
                file_exists($path . '/index.php'))
            {
                if ($versionMajor < 8) {
                    $includePath = 'includes';
                } else {
                    $includePath = 'core/includes';
                }

                $bootstrapInc = $path . '/' . $includePath . '/bootstrap.inc';

                if (!is_file($bootstrapInc)) {

                    // It's configured, but wrongly configured, alert user
                    trigger_error(sprintf(
                        "Configured Drupal path is a not a Drupal installation" +
                        " or version mismatch: '%s' (version major: %d)",
                        $path, $versionMajor));

                    return null;
                }

                if (!$handle = fopen($bootstrapInc, 'r')) {
                    trigger_error(sprintf("Cannot open for reading: '%s'", $bootstrapInc));
                    return null;
                }

                $buffer = fread($handle, 512);
                fclose($handle);

                if (preg_match("/^\s*define\('VERSION', '([^']+)'/ims", $buffer, $matches)) {
                    list($parsedMajor) = explode('.', $matches[1]);
                }

                if (!isset($parsedMajor) || empty($parsedMajor)) {
                    trigger_error(sprintf("Could not parse core version in: '%s'", $bootstrapInc));
                    return null;
                }

                // We are OK to go
                define('DRUPAL_ROOT', $path);
                require_once $bootstrapInc;

                self::$bootstrapped = true;
                self::$bootstrappedVersionMajor = $versionMajor;

                switch ($versionMajor) {

                    case 7:
                        drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);
                        return self::$databaseConnection = \Database::getConnection();

                    case 8:
                        // TODO
                    default:
                        throw new \Exception(sprintf(
                            "Drupal version unsupported yet: %s", $versionMajor));
                }
            }
        }

        return null;
    }
}
