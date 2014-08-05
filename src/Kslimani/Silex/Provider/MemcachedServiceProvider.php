<?php

namespace Kslimani\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class MemcachedServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {

        if (!extension_loaded('memcached')) {
            throw new \Exception("memcached PHP extension not loaded");
        }

        $app['mcs.options.initializer'] = $app->protect(function () use ($app) {

            static $initialized = false;

            if ($initialized) {
                return;
            }

            if (!isset($app['mcs.options'])) {
                $app['mcs.options'] = array(
                    'default' => (isset($app['mc.options'])) ? $app['mc.options'] : array('servers' => array(array('127.0.0.1', 11211)))
                );
            }

            if (!isset($app['mcs.default'])) {
                $app['mcs.default'] = 'default';
                $tmp = $app['mcs.options'];
                if (!isset($tmp['default'])) {
                    reset($tmp);
                    $app['mcs.default'] = key($tmp);
                }
            }
        });

        $app['mcs'] = $app->share(function ($app) {

            $app['mcs.options.initializer']();

            $mcs = new \Pimple();
            foreach ($app['mcs.options'] as $name => $options) {
                $mcs[$name] = $mcs->share(function ($mcs) use ($options) {
                    $memcached = (isset($options['persistent_id'])) ? new \Memcached($options['persistent_id']) : new \Memcached();

                    if (isset($options['options'])) {
                        $memcached->setOptions($options['options']);
                    }

                    if (count($memcached->getServerList()) == 0) {
                        $memcached->addServers($options['servers']);
                    }

                    return $memcached;
                }); 
            }

            return $mcs;
        });

        // shortcuts for the "first" Memcached instance
        $app['memcached'] = $app['mc'] = function($app) {
            $mcs = $app['mcs'];

            return $mcs[$app['mcs.default']];
        };
    }

    public function boot(Application $app)
    {
    }

}
