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
    $servers = [];

    if (isset($app['memcache.server']) && !empty($app['memcache.server'])) {
      $servers = $app['memcache.server'];
    }

    if (getenv('MEMCACHED_PORT_11211_TCP_ADDR') && getenv('MEMCACHED_PORT_11211_TCP_PORT')) {
      $servers = [
        [getenv('MEMCACHED_PORT_11211_TCP_ADDR'), getenv('MEMCACHED_PORT_11211_TCP_PORT')],
      ];
    }
    if (empty($servers)) {
      $servers[] = ['127.0.0.1', 11211];
    }

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
   * @param $keyname
   * @param $callable
   * @param $dataTTL
   *
   * @return mixed
   */
  private function getFromMemcache($keyname, $callable, $dataTTL) {
    $data = $this->memcache->get($keyname);
    if ($data !== false) {
      $data = $callable();
      if ($data) {
        $this->memcache->set($keyname, $data, $dataTTL);
      }
    }

    return $data;
  }

  /**
   * @param string   $keyname
   * @param callable $callable
   * @param int      $refreshCacheTTL
   * @param int      $dataTTL
   *
   * @return array|string
   */
  public function get($keyname, $callable, $refreshCacheTTL = 250, $dataTTL = 300) {
    $keyname = md5($keyname);
    if ($dataTTL > $refreshCacheTTL) {
      $ttlProtectionKey      = 'TTL_' . md5($keyname);
      $ttlProtectionMutexKey = 'M' . $ttlProtectionKey;

      if ($this->memcache->get($ttlProtectionKey) === false) {
        if ($this->memcache->get($ttlProtectionMutexKey) === false) {
          $this->memcache->set($ttlProtectionMutexKey, '1');
          $data = $callable();
          if ($data) {
            $this->memcache->set($keyname, $data, $dataTTL);
          }
          $this->memcache->set($ttlProtectionKey, 1, $refreshCacheTTL);
          $this->memcache->delete($ttlProtectionMutexKey);

          return $data;
        }
      }

      return $this->getFromMemcache($keyname, $callable, $dataTTL);
    } else {
      return $this->getFromMemcache($keyname, $callable, $dataTTL);
    }
  }
}


