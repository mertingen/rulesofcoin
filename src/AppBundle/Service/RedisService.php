<?php
/**
 * Created by IntelliJ IDEA.
 * User: mert
 * Date: 1/9/18
 * Time: 4:29 PM
 */

namespace AppBundle\Service;

use Doctrine\Common\Cache\Cache;

class RedisService
{
    private $cache;

    /**
     * RedisService constructor.
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    //lifetime => verinin ne kadar süre tutulacağı giriliyor. Saniye cinsinden değer alıyor.
    //15778476 saniye 6 aya denk geliyor.
    //key ve value girilerek veri kaydediliyor.
    public function insert($key, $value, $lifetime = 15778476)
    {
        return $this->cache->save($key, $value, $lifetime);
    }

    //key gönderilerek istenilen veri alınıyor.
    public function get($key)
    {
        return $this->cache->fetch($key);
    }

    //key gönderilerek istenilen veri siliniyor
    public function delete($key)
    {
        return $this->cache->delete($key);
    }

}