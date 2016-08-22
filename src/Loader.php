<?php

namespace Battis\MySQLSchemaLoader;

use mysqli;
use Battis\MySQLSchemaLoader\Exceptions\LoaderException;

/**
 * Load a MySQL schema stored as a series of SQL queries into a database.
 *
 * @author Seth Battis <seth@battis.net>
 * @version v1.0
 */
class Loader
{
    /**
     * MySQL database connection object
     * @var mysqli
     */
    protected $mysql = null;

    /**
     * SQL query to load schema into database
     * @var string
     */
    protected $schema = null;

    /**
     * Create a loader instance
     *
     * @param mysqli $mysql MySQL database connection
     * @param string $schema Path to *.sql schema file or a literal SQL query
     *     to load the schema
     * @throws LoaderException `LoaderException::CONFIGURATION` if `$schema` is
     *     empty
     */
    public function __construct(mysqli $mysql, $schema)
    {
        $this->mysql = $mysql;
        if (file_exists($schema)) {
            $this->schema = file_get_contents($schema);
        } elseif (!empty($schema)) {
            $this->schema = $schema;
        } else {
            throw new LoaderException(
                'Missing schema',
                LoaderException::CONFIGURATION
            );
        }
    }

    /**
     * Is the `LTI_Tool_Provider` database schema loaded already?
     *
     * @return boolean|string[] `false` if all tables created in schema are
     *     present, otherwise a list of missing tables
     */
    public function test()
    {
        $missingTables = false;
        foreach (explode(';', $this->schema) as $query) {
            if (preg_match('/create\s+table\s+(if\s+not\s+exists\s+)?((`[^`]+`)|(\w+))/i', $query, $match)) {
                if ($this->mysql->query("SHOW TABLES LIKE '{$match[2]}'")->num_rows != 1) {
                    $missingTables[] = $match[2];
                }
            }
        }
        return $missingTables;
    }

    /**
     * Find the position of the first occurrence of any of several substrings
     * in a string
     *
     * @param string $haystack The string to search in.
     * @param mixed $needles Array of strings to search for. If a needle is
     *     not a string, it is converted to an integer and applied as the
     *     ordinal value of a character.
     * @return int|boolean Returns the position of where the first found needle
     *    exists relative to the beginning of the haystack string (independent
     *    of offset). Also note that string positions start at 0, and not 1.
     *    Returns `false` if the needle was not found.
     */
    private function strposArray($haystack, $needles)
    {
        if (is_array($needles)) {
            foreach ($needles as $needle) {
                if (($strpos = strpos($haystack, $needle)) !== false) {
                    return $strpos;
                }
            }
        }
        return false;
    }

    /**
     * Load the schema into the database
     *
     * If `$test` is set to `true`, this method will test to see if any tables
     * created by the schema are missing. If any tables are missing, then only
     * the queries affecting those tables will be run. Otherwise, if `$test` is
     * `false`, all queries in the schema will be run blindly, potentially
     * throwing `LoaderException` if an already-created table is recreated.
     *
     * @param boolean $test Whether or not to test for missing tables
     * @return boolean|string[] (Optional, defaults to `true`) If `$test` is
     *     `true`, returns either a list of missing tables or `false` if no
     *     tables were missing, otherwise returns `true` on success.
     * @throws LoaderException `LoaderException::MYSQL` on MySQL query error
     */
    public function load($test = true)
    {
        $missingTables = false;
        if ($test) {
            $missingTables = $this->test();
        }

        if (!$test || ($test && $missingTables !== false)) {
            foreach (explode(';', $this->schema) as $query) {
                if (!empty(trim($query))) {
                    if (!$test || $this->strposArray($query, $missingTables) !== false) {
                        if ($this->mysql->query($query) === false) {
                            throw new LoaderException(
                                "Error {$this->mysql->errno}: {$this->mysql->error}",
                                LoaderException::MYSQL
                            );
                        }
                    }
                }
            }
        }

        return ($test ? $missingTables : true);
    }
}
