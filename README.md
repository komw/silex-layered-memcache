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
Example:
```
$app->register(new LayeredMemcache(),
    [
     'memcache.server'  => [array(
       ['127.0.0.1', 11211],
    ]
);

//OR

$app->register(new LayeredMemcache());


$data = $app['layered_memcache']->get('key_name', function () {
          //gettting data from DB,etc.
          return $data;
        }
);
```


License
-------

'Silex-Layered-Memcache' is licensed under the MIT license.
