<?php \defined('SYSPATH') or die('No direct script access.');

/**
 * PHPUnit testsuite for kohana application
 *
 * @package    Kohana/UnitTest
 * @author     Kohana Team
 * @author     BRMatt <matthew@sigswitch.com>
 * @author	   Paul Banks
 * @copyright  (c) 2008-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Kohana_Unittest_Tests {
	static protected $cache = array();

	/**
	 * Loads test files if they cannot be found by kohana
	 * @param <type> $class
	 */
	static function autoload($class)
	{
		$file = \str_replace('_', '/', $class);

		if ($file = Kohana::find_file('tests', $file))
		{
			require_once $file;
		}
	}

	/**
	 * Configures the environment for testing
	 *
	 * Does the following:
	 *
	 * * Loads the phpunit framework (for the web ui)
	 * * Restores exception phpunit error handlers (for cli)
	 * * registeres an autoloader to load test files
	 */
	static public function configure_environment($do_whitelist = TRUE, $do_blacklist = TRUE)
	{
		\restore_exception_handler();
		\restore_error_handler();

		\spl_autoload_register(array('Unittest_tests', 'autoload'));

		Unittest_tests::$cache = (($cache = Kohana::cache('unittest_whitelist_cache')) === NULL) ? array() : $cache;

	}

	/**
	 * Creates the test suite for kohana
	 *
	 * @return \PHPUnit\Framework\TestSuite
	 */
	static function suite()
	{
		static $suite = NULL;

		if ($suite instanceof \PHPUnit\Framework\TestSuite)
		{
			return $suite;
		}

		Unittest_Tests::configure_environment();

		$suite = new \PHPUnit\Framework\TestSuite;

		// Add tests
		$files = Kohana::list_files('tests');
		self::addTests($suite, $files);

		return $suite;
	}

	/**
	 * Add files to test suite $suite
	 *
	 * Uses recursion to scan subdirectories
	 *
	 * @param \PHPUnit\Framework\TestSuite  $suite   The test suite to add to
	 * @param array                        $files   Array of files to test
	 */
	static function addTests(\PHPUnit\Framework\TestSuite $suite, array $files)
	{

		foreach ($files as $path => $file)
		{
			if (\is_array($file))
			{
				if ($path != 'tests'.DIRECTORY_SEPARATOR.'test_data')
				{					
					self::addTests($suite, $file);
				}
			}
			else
			{
				// Make sure we only include php files
				if (\is_file($file) AND \substr($file, -\strlen(EXT)) === EXT)
				{
					// The default PHPUnit TestCase extension
					if ( ! \strpos($file, 'TestCase'.EXT))
					{
						$suite->addTestFile($file);
					}
					else
					{
						require_once($file);
					}

				}
			}
		}
	}

	/**
	 * Blacklist a set of files in PHPUnit code coverage
	 *
	 * @param array $blacklist_items A set of files to blacklist
	 * @param Unittest_TestSuite $suite The test suite
	 */
	static public function blacklist(array $blacklist_items, Unittest_TestSuite $suite = NULL)
	{
        throw new \BadMethodCallException();
	}

	/**
	 * Sets the whitelist
	 *
	 * If no directories are provided then the function'll load the whitelist
	 * set in the config file
	 *
	 * @param array $directories Optional directories to whitelist
	 * @param Unittest_Testsuite $suite Suite to load the whitelist into
	 */
	static public function whitelist(array $directories = NULL, Unittest_TestSuite $suite = NULL)
	{
        throw new \BadMethodCallException();
	}

	/**
	 * Works out the whitelist from the config
	 * Used only on the CLI
	 *
	 * @returns array Array of directories to whitelist
	 */
	static protected function get_config_whitelist()
	{
        throw new \BadMethodCallException();
	}

	/**
	 * Recursively whitelists an array of files
	 *
	 * @param array $files Array of files to whitelist
	 * @param Unittest_TestSuite $suite Suite to load the whitelist into
	 */
	static protected function set_whitelist($files, Unittest_TestSuite $suite = NULL)
	{
	    throw new \BadMethodCallException();
	}
}
