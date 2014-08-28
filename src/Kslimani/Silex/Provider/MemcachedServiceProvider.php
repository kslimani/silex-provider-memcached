<?php

namespace Kslimani\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Memcached;
use Exception;

class MemcachedServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!extension_loaded('memcached')) {
            throw new Exception("memcached PHP extension not loaded");
        }

        // Default configuration
        if (!isset($app['memcached.config'])) {
            $app['memcached.config'] = array('default' => array(
                'servers' => array(array('127.0.0.1', 11211))
            ));
        }

        $app['memcached.factory'] = $app->protect(function ($name, &$conf) use ($app) {

            if (!isset($conf['servers']) || empty($conf['servers'])) {
                throw new Exception(
                    sprintf("Memcached 'server' configuration is missing or invalid for %s connection pool", $name)
                );
            }

            $app['mc.'.$name] = $app->share(function () use ($app, $conf) {
                $mc = (isset($conf['persistent_id'])) ? new Memcached($conf['persistent_id']) : new Memcached();

                if (isset($conf['options'])) {
                    $mc->setOptions($conf['options']); // See Memcached::setOptions
                }

                if (0 === count($mc->getServerList())) {
                    $mc->addServers($conf['servers']); // See Memcached::addServers
                }

                return $mc;
            });
        });
    }

    public function boot(Application $app)
    {
        foreach ($app['memcached.config'] as $pool => $config) {

            if (!isset($app['memcached.factory.defaut_pool'])) {
                $app['memcached.factory.defaut_pool'] = 'mc.'.$pool;
            }

            $app['memcached.factory']($pool, $config);

            // Default connection pool shortcuts
            if (!isset($app['memcached'])) {
                $app['memcached'] = $app['mc'] = $app->share(function ($app) {
                    return $app[$app['memcached.factory.defaut_pool']];
                });
            }
        }
    }

}
