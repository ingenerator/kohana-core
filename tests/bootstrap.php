<?php

// Set up temporary directories for the app
// These need to exist and be writable, though we do very little with them
(function() {
    $mkdir = function (string $dir): string {
        if (!mkdir($dir, recursive: true)) {
            throw new RuntimeException('Could not create temporary directory in '.$dir);
        }
        return $dir;
    };

    $basedir = uniqid(sys_get_temp_dir().'/kohana-tests/');
    define('APPPATH', $mkdir($basedir.'/application').'/');
    $mkdir(APPPATH.'cache');
    $mkdir(APPPATH.'logs');
    define('MODPATH', $mkdir($basedir.'/modules').'/');
    define('DOCROOT', $mkdir($basedir.'/htdocs').'/');
})();

define('SYSPATH', realpath(__DIR__.'/../').'/');
define('EXT', '.php');

error_reporting(E_ALL);

define('KOHANA_START_TIME', microtime(TRUE));
define('KOHANA_START_MEMORY', memory_get_usage());


// Load the core Kohana class and extension
require SYSPATH.'classes/Kohana/Core'.EXT;
require SYSPATH.'classes/Kohana'.EXT;

// Set default runtime environment (based on default Kohana install)
date_default_timezone_set('America/Chicago');
setlocale(LC_ALL, 'en_US.utf-8');
mb_substitute_character('none');

// Enable the Kohana auto-loader and enable it for unserialization
spl_autoload_register(array('Kohana', 'auto_load'));
ini_set('unserialize_callback_func', 'spl_autoload_call');

// Configure and initialize Kohana
I18n::lang('en-us');

// -- Configuration and initialization -----------------------------------------

if (isset($_SERVER['SERVER_PROTOCOL']))
{
    // Replace the default protocol.
    HTTP::$protocol = $_SERVER['SERVER_PROTOCOL'];
}

/**
 * Set Kohana::$environment if a 'KOHANA_ENV' environment variable has been supplied.
 *
 * Note: If you supply an invalid environment name, a PHP warning will be thrown
 * saying "Couldn't find constant Kohana::<INVALID_ENV_NAME>"
 */
if (isset($_SERVER['KOHANA_ENV']))
{
    Kohana::$environment = constant('Kohana::'.strtoupper($_SERVER['KOHANA_ENV']));
}

/**
 * Initialize Kohana, setting the default options.
 *
 * The following options are available:
 *
 * - string   base_url    path, and optionally domain, of your application   NULL
 * - string   index_file  name of your index file, usually "index.php"       index.php
 * - string   charset     internal character set used for input and output   utf-8
 * - string   cache_dir   set the internal cache directory                   APPPATH/cache
 * - integer  cache_life  lifetime, in seconds, of items cached              60
 * - boolean  errors      enable or disable error handling                   TRUE
 * - boolean  profile     enable or disable internal profiling               TRUE
 * - boolean  caching     enable or disable internal caching                 FALSE
 * - boolean  expose      set the X-Powered-By header                        FALSE
 */
Kohana::init(array(
    'base_url'   => '/kohana/',
));

/**
 * Attach the file write to logging. Multiple writers are supported.
 */
Kohana::$log->attach(new Log_File(APPPATH.'logs'));

/**
 * Attach a file reader to config. Multiple readers are supported.
 */
Kohana::$config->attach(new Config_File);

Kohana::modules(
    (function () {
        $modules = ['unittest' => __DIR__.'/../modules/unittest'];
        $env = getenv('KOHANA_MODULES');
        if (empty($env)) {
            throw new RuntimeException('KOHANA_MODULES environment variable must be set');
        }

        foreach (explode(',', $env) as $module) {
            if ($module === 'Core') {
                continue;
            }
            $modules[strtolower($module)] = match ($module) {
                'Minion' => realpath(__DIR__.'/../modules/minion'),
                default => throw new RuntimeException('Unknown module: '.$module)
            };
        }
        return $modules;
    })(),
);


/**
 * Cookie Salt
 * @see  http://kohanaframework.org/3.3/guide/kohana/cookies
 *
 * If you have not defined a cookie salt in your Cookie class then
 * uncomment the line below and define a preferrably long salt.
 */
// Cookie::$salt = NULL;

/**
 * Set the routes. Each route must have a minimum of a name, a URI and a set of
 * defaults for the URI.
 */
Route::set('default', '(<controller>(/<action>(/<id>)))')
    ->defaults(array(
        'controller' => 'welcome',
        'action'     => 'index',
    ));
