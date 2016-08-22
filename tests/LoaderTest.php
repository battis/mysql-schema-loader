<?php

namespace Testing;

use mysqli;
use Battis\MySQLSchemaLoader\Loader;
use Battis\MySQLSchemaLoader\Exceptions\LoaderException;

class LoaderTest extends \PHPUnit_Framework_TestCase
{
    protected $mysql;

    public function __construct(...$param)
    {
        parent::__construct(...$param);
        $this->mysql = new mysqli(
            'localhost',
            'phpunit',
            'phpunit',
            'phpunit'
        );
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function testNullMySQLiInstance()
    {
        new Loader(null, 'foobar');
    }

    public function testEmptySchema()
    {
        $exceptionThrown = false;
        try {
            new Loader($this->mysql, null);
        } catch (LoaderException $e) {
            $this->assertEquals(LoaderException::CONFIGURATION, $e->getCode());
            $exceptionThrown = true;
        }
        $this->assertEquals(true, $exceptionThrown);
    }

    public function testValidSchemaPath()
    {
        new Loader($this->mysql, __DIR__ . '/schema.sql');
    }

    public function testValidSchemaQuery()
    {
        new Loader($this->mysql, file_get_contents(__DIR__ . '/schema.sql'));
    }

    /*
     * TODO deal with DBUnit, for now, assuming we start with an empty database
     */
    public function testTestSchemaFromPath()
    {
        $loader = new Loader($this->mysql, __DIR__ . '/schema.sql');
        $this->assertEquals(['lti_consumer', 'lti_context', 'lti_user', 'lti_nonce', 'lti_share_key'], $loader->test());
    }

    public function testLoadSchemaFromPath1()
    {
        $loader = new Loader($this->mysql, __DIR__ . '/schema.sql');
        $this->assertEquals(['lti_consumer', 'lti_context', 'lti_user', 'lti_nonce', 'lti_share_key'], $loader->load());
    }

    public function testLoadSchemaFromPath2()
    {
        $loader = new Loader($this->mysql, __DIR__ . '/schema.sql');
        $this->assertEquals(false, $loader->load());
    }

    public function testLoadSchemaFromPathWithoutTest()
    {
        $loader = new Loader($this->mysql, __DIR__ . '/schema.sql');
        $exceptionThrown = false;
        try {
            $loader->load(false);
        } catch (LoaderException $e) {
            $this->assertEquals(LoaderException::MYSQL, $e->getCode());
            $exceptionThrown = true;
        }
        $this->assertEquals(true, $exceptionThrown);
    }

    public function testPartialLoadSchemaFromPath()
    {
        $loader = new Loader($this->mysql, __DIR__ . '/schema.sql');
        $this->mysql->query('DROP TABLE lti_nonce');
        $this->assertEquals(['lti_nonce'], $loader->load());
    }
}
