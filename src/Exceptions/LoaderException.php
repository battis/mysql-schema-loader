<?php
/** LoaderException class */

namespace Battis\MySQLSchemaLoader\Exceptions;

/**
 * Exceptions thrown by LoaderTest
 *
 * @author Seth Battis <seth@battis.net>
 * @version v1.0
 */
class LoaderException extends \Exception
{
    const CONFIGURATION = 1;
    const MYSQL = 2;
}
