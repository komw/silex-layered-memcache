<?php

namespace komw\silex\memcache;

use Memcached;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 *
 * User: Szymon Gładysz (komw@sgladysz.com)
 * Date: 25.04.2016
 */
class LayeredMemcache implements ServiceProviderInterface
{
  /**
   * @param Application $app
   */
  public function boot(Application $app) {
    $servers = isset($app['memcache.server']) ? $app['memcache.server'] : array(
      array('127.0.0.1', 11211),
    );

    $memcache = new \Memcached(serialize($servers));
    if (!count($memcache->getServerList())) {
      foreach ($servers as $config) {
        call_user_func_array(array($memcache, 'addServer'), array_values($config));
      }
    }

    $this->memcache = $memcache;
  }

  /**
   * @var Memcached
   */
  private $memcache = null;

  /**
   * @param Application $app
   *
   * @throws \Exception
   */
  public function register(Application $app) {
    $app['layered_memcache'] = $this;
  }


  /**
   * @param string   $keyname
   * @param callable $callable
   * @param int      $ttl
   *
   * @return array|string
   */
  public function get($keyname, $callable, $ttl = 300) {

    $keyname = md5($keyname);
    if ($ttl >= 5) {
      $ttlProtectionKey      = 'TTL_' . md5($keyname);
      $ttlProtectionMutexKey = 'M' . $ttlProtectionKey;

      $keyTtl = $this->memcache->get($ttlProtectionKey);
      if ((!$keyTtl)) {
        if (!$this->memcache->get($ttlProtectionMutexKey)) {
          $this->memcache->set($ttlProtectionMutexKey, '1');
          $data = $callable();
          var_dump('API CAL!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!');
          $this->memcache->set($ttlProtectionKey, 1, $ttl - 5);
          $this->memcache->set($keyname, $data, $ttl);
          $this->memcache->delete($ttlProtectionMutexKey);

          return $data;
        }
      }

      return $this->memcache->get($keyname);
    } else {
      $data = $this->memcache->get($keyname);
      if (!$data) {
        $data = $callable();
        $this->memcache->set($keyname, $data, $ttl);
      }

      return $data;
    }
  }
}
