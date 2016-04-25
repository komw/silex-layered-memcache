Silex-Layered-Memcache
================

Installation
------------

Create a composer.json in your projects root-directory:
```
{
    "require": {
        "komw/silex-layered-memcache": "1.*"
    }
}
```

More Information
----------------
```
$app->register(new LayeredMemcache(),
    [
     'memcache.library' => 'memcached',
     'memcache.server'  => array(
       array('127.0.0.1', 11211),
    ]
));


$data = $app['layered_memcache']->get('Keyname', function () {
          //gettting data from DB,etc.
          return $data;
        }
);
```

License
-------

'Silex-Layered-Memcache' is licensed under the MIT license.
