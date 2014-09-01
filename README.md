# silex-provider-memcached

A Memcached service provider for the [Silex](http://silex.sensiolabs.org/) micro-framework.


## Limitations

Only [memcached](http://pecl.php.net/package/memcached) PHP extension is supported.


## Registering

Add in `composer.json` file :

```json
{
    "require": {
        "kslimani/silex-provider-memcached": "1.0.*"
    },
    "repositories": [
      {
        "type": "vcs",
        "url": "https://github.com/kslimani/silex-provider-memcached.git"
      }
    ]
}
```

In Silex application add :

```php

use Kslimani\Silex\Provider\MemcachedServiceProvider;

$app->register(new MemcachedServiceProvider());

```

Default service configuration is single server pool using `127.0.0.1` as host and `11211` as port.


## Usage

The Memcached provider provide a `memcached` service (and also a `mc` shortcut) :

```php

$app->get('/foo', function () use ($app) {
    $value = $app['memcached']->get('foo');

    return new Response(sprintf("foo value is %s", $value));
});

```

## Configuration

* `memcached.config` : a key/value indexed array of Memcached connections pool.

Default service provider configuration is :

```php

array('memcached.config' => array(
    'default' => array(
        'servers' => array(array(127.0.0.1, 11211))
    )
))

```

Available configuration parameters are :

* `persistant_id` : Optionnal. Specify a unique ID for the instance. (see [Memcached::__construct](http://php.net/manual/en/memcached.construct.php))
* `servers` : Array of the servers to add to the pool. (see [Memcached::addServers](http://php.net/manual/en/memcached.addservers.php))
* `options` : Optionnal. An associative array of options. (see [Memcached::setOptions](http://php.net/manual/en/memcached.setoptions.php))

```php

// Example with a pool of 3 servers
$app->register(new MemcachedServiceProvider(), array('memcached.config' => array(
    'default' => array(
        'servers' => array(
            array('192.168.1.101', 11211),
            array('192.168.1.102', 11211),
            array('192.168.1.103', 11211)
        )
    )
)));

// Example with persistent connections and weight
$app->register(new MemcachedServiceProvider(), array('memcached.config' => array(
    'default' => array(
        'persistent_id' => 'unique_pool_id',
        'servers' => array(
            array('192.168.1.101', 11211, 33),
            array('192.168.1.102', 11211, 67)
        )
    )
)));

// Example with setting options
$app->register(new MemcachedServiceProvider(), array('memcached.config' => array(
    'default' => array(
        'servers' => array(
            array('192.168.1.101', 11211)
        ),
        'options' => array(
            \Memcached::OPT_SERIALIZER => \Memcached::SERIALIZER_IGBINARY,
            \Memcached::OPT_BINARY_PROTOCOL => true
        )
    )
)));

```


## Using multiple pools

The Memcached provider can allow access to multiple pool instances :

```php

$app->register(new MemcachedServiceProvider(), array('memcached.config' => array(
    'first_pool' => array(
        'persistent_id' => 'unique_pool_id',
        'servers' => array(
            array('192.168.1.101', 11211),
            array('192.168.1.102', 11211)
        ),
        'options' => array(
            \Memcached::OPT_SERIALIZER => \Memcached::SERIALIZER_IGBINARY,
            \Memcached::OPT_BINARY_PROTOCOL => true
        ),
    ),
    'second_pool' => array(
        'servers' => array(
            array('localhost', 11211)
        ),
    )
)));

```

Each registered pool can be accessed using 'mc.POOLNAME' key naming nomenclature :

```php

$foo = $app['mc.first_pool']->get('foo');
$bar = $app['mc.second_pool']->get('bar');

```

The first registered pool is the default and can simply be accessed as you would if there was only one pool. Given the above configuration, these three lines are equivalent :

```php

$app['memcached']->set('foobar', 1234);

$app['mc']->set('foobar', 1234);

$app['mc.first_pool']->set('foobar', 1234);

```

## Development

Run PHP test suites :

```shell
make test
```

Generate PHP code coverage report (require XDebug PHP extension) :

```shell
make cc
```

Run PHP Coding Standards Fixer preview :

```shell
make csp
```

Run PHP Coding Standards Fixer :

```shell
make cs
```

Delete code coverage report :

```shell
make clean
```
