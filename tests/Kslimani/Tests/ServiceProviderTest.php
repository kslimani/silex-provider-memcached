<?php

namespace Kslimani\Tests;

use Silex\Application;
use Kslimani\Silex\Provider\MemcachedServiceProvider;
use Memcached;

class ServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    protected function appFactory($memcachedOptions = array())
    {
        $app = new Application();
        $app->register(new MemcachedServiceProvider(), $memcachedOptions);
        $app->boot();

        return $app;
    }

    public function testDefaultConfiguration()
    {
        $app = $this->appFactory();

        // Ensure default configuration exists
        $this->assertArrayHasKey('memcached.config', $app);
        $this->assertArrayHasKey('default', $app['memcached.config']);

        // Ensure default configuration is correct
        $pool = $app['memcached.config']['default'];
        $this->assertArrayHasKey('servers', $pool);
        $this->assertEquals(array(array('127.0.0.1', 11211)), $pool['servers']);

        // Ensure default connection pool exists
        $this->assertArrayHasKey('mc.default', $app);
        $this->assertInstanceOf('Memcached', $app['mc.default']);

        // Ensure default connection pool is properly configured
        $servers = $app['mc.default']->getServerList();
        $this->assertCount(1, $servers);

        $server = $servers[0];
        $this->assertArrayHasKey('host', $server);
        $this->assertArrayHasKey('port', $server);
        $this->assertEquals('127.0.0.1', $server['host']);
        $this->assertEquals(11211, $server['port']);

        // Ensure shortcuts are valid
        $this->assertInstanceOf('Memcached', $app['mc']);
        $this->assertInstanceOf('Memcached', $app['memcached']);
        $this->assertEquals($app['mc'], $app['memcached']);
    }

    public function testCustomConfiguration()
    {
        $app = $this->appFactory(array('memcached.config' => array(
            'first_pool' => array(
                'persistent_id' => 'first_pool_id',
                'servers' => array(
                    array('127.0.0.1', 11211),
                    array('127.0.1.1', 11212, 10)
                ),
                'options' => array(
                    Memcached::OPT_COMPRESSION => false,
                    Memcached::OPT_HASH => Memcached::HASH_MD5
                )
            ),
            'second_pool' => array(
                'servers' => array(
                    array('127.0.0.1', 22122)
                ),
                'options' => array(
                    Memcached::OPT_PREFIX_KEY => 'test'
                )
            ),
        )));

        // Ensure custom configuration exists
        $this->assertArrayHasKey('memcached.config', $app);
        $this->assertArrayHasKey('first_pool', $app['memcached.config']);
        $this->assertArrayHasKey('second_pool', $app['memcached.config']);
        $firstPoolOptions = $app['memcached.config']['first_pool'];
        $secondPoolOptions = $app['memcached.config']['second_pool'];

        // Ensure custom connection pools exists
        $this->assertArrayHasKey('mc.first_pool', $app);
        $this->assertArrayHasKey('mc.second_pool', $app);
        $firstPool = $app['mc.first_pool'];
        $secondPool = $app['mc.second_pool'];
        $this->assertInstanceOf('Memcached', $firstPool);
        $this->assertInstanceOf('Memcached', $secondPool);

        // Ensure first pool is properly configured
        $expectedServerList = array(
            array('host' => '127.0.0.1', 'port' => 11211),
            array('host' => '127.0.1.1', 'port' => 11212) // 'weight' no longer returned
        );
        $this->assertTrue($firstPool->isPersistent());
        $this->assertEquals($expectedServerList, $firstPool->getServerList());
        foreach ($app['memcached.config']['first_pool']['options'] as $option => $expectedValue) {
            $this->assertEquals($expectedValue, $firstPool->getOption($option));
        }

        // Ensure second pool is properly configured
        $expectedServerList = array(
            array('host' => '127.0.0.1', 'port' => 22122),
        );
        $this->assertNotTrue($secondPool->isPersistent());
        $this->assertEquals($expectedServerList, $secondPool->getServerList());
        foreach ($app['memcached.config']['second_pool']['options'] as $option => $expectedValue) {
            $this->assertEquals($expectedValue, $secondPool->getOption($option));
        }
    }

    /**
     * @expectedException Exception
     */
    public function testBadConfiguration()
    {
        $app = $this->appFactory(array('memcached.config' => array(
            'bad_pool' => array(
                'persistent_id' => 'pool_id'
            ),
        )));
    }

}
