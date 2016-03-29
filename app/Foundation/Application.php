<?php
/**
 * Author: Abel Halo <zxz054321@163.com>
 */

namespace App\Foundation;

use Phalcon\Config;
use Phalcon\Di;
use Phalcon\Logger\Adapter\File as FileAdapter;

class Application
{
    /**
     * The Lightning framework version.
     *
     * @var string
     */
    const VERSION = '0.0.0 (Unstable)';

    /**
     * @var \Phalcon\DiInterface
     */
    protected $di;
    protected $config;

    public function __construct()
    {
        $this->config = new Config(array_replace_recursive(
            require CONFIG_PATH.'/config.php',
            require ROOT.'/_'
        ));

        /** @noinspection PhpUndefinedFieldInspection */
        date_default_timezone_set($this->config->timezone);

        $this->di = Di::getDefault();
        $this->di->set('config', $this->config);
    }

    public function di()
    {
        return $this->di;
    }

    public function registerExceptionHandler()
    {
        // Global error handler & logger
        register_shutdown_function(function () {
            if (!is_null($error = error_get_last())) {

                /** @noinspection PhpUndefinedFieldInspection */
                if (!$this->config->debug && $error['type'] >= E_NOTICE) {
                    return;
                }

                $logger = new FileAdapter(ROOT.'/storage/logs/error.log');

                $logger->error(print_r($error, true));
            }
        });

        // Report errors in debug mode only
        /** @noinspection PhpUndefinedFieldInspection */
        if (!$this->config->debug) {
            error_reporting(0);
        }
    }

    /**
     * Register all of the service providers.
     *
     * @return void
     */
    public function registerServiceProviders(array $providers)
    {
        foreach ($providers as $provider) {
            $provider = new $provider($this->di, $this->config);
            $this->di = $provider->inject();
        }
    }
}