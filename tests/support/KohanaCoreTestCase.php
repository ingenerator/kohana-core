<?php

namespace tests\Kohana\Support;

use PHPUnit\Framework\Attributes\UsesClass;
use BadMethodCallException;
use PHPUnit\Framework\TestCase;

/**
 * A version of the stock PHPUnit testcase that includes some extra helpers
 * and default settings
 */
#[UsesClass(Unittest_TestCase::tag_match)]
abstract class KohanaCoreTestCase extends TestCase
{

    /**
     * Make sure PHPUnit backs up globals
     * @var boolean
     */
    protected $backupGlobals = FALSE;

    /**
     * A set of unittest helpers that are shared between normal / database
     * testcases
     */
    protected KohanaTestHelpers $_helpers;

    /**
     * A default set of environment to be applied before each test
     * @var array
     */
    protected $environmentDefault = array();

    /**
     * Creates a predefined environment using the default environment
     *
     * Extending classes that have their own setUp() should call
     * parent::setUp()
     */
    public function setUp(): void
    {
        $this->_helpers = new KohanaTestHelpers;

        $this->setEnvironment($this->environmentDefault);
    }

    /**
     * Restores the original environment overriden with setEnvironment()
     *
     * Extending classes that have their own tearDown()
     * should call parent::tearDown()
     */
    public function tearDown(): void
    {
        $this->_helpers->restore_environment();
    }

    /**
     * Removes all kohana related cache files in the cache directory
     */
    public function cleanCacheDir()
    {
        return KohanaTestHelpers::clean_cache_dir();
    }

    /**
     * Helper function that replaces all occurences of '/' with
     * the OS-specific directory separator
     *
     * @param string $path The path to act on
     * @return string
     */
    public static function dirSeparator($path)
    {
        return KohanaTestHelpers::dir_separator($path);
    }

    /**
     * Allows easy setting & backing up of enviroment config
     *
     * Option types are checked in the following order:
     *
     * * Server Var
     * * Static Variable
     * * Config option
     *
     * @param array $environment List of environment to set
     */
    public function setEnvironment(array $environment):void
    {
        $this->_helpers->set_environment($environment);
    }

    /**
     * Check for internet connectivity
     *
     * @return boolean Whether an internet connection is available
     */
    public function hasInternet()
    {
        return KohanaTestHelpers::has_internet();
    }

    /**
     * Evaluate an HTML or XML string and assert its structure and/or contents.
     * @deprecated
     * @param array $matcher
     * @param string $actual
     * @param string $message
     * @param bool $isHtml
     */
    public static function assertTag($matcher, $actual, $message = '', $isHtml = true)
    {
        throw new BadMethodCallException();
    }

}
