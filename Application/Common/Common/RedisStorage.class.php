<?php

namespace Common\Common;

class RedisStorage
{
    /**
     * @var Redis
     */
    protected $redis;

    /**
     * RedisStorage constructor.
     * @param Redis $redis
     */
    public function __construct()
    {
        $arr = require APP_PATH . 'Common/Conf/redis.php';
        $arr = $arr[APP_STATUS];
        $this->redis = new \Redis();
        $this->redis->connect($arr['host'], $arr['port']);

        $this->redis->auth($arr['auth']);
        $this->redis->select($arr['db']);
    }

    public function pushQueue($key, $value)
    {
        $this->redis->lPush($key, $value);
    }

    public function delete($key)
    {
        if ($this->redis->exists($key)){
            $this->redis->delete($key);
        }
    }

    public function getHashCache($hash, $key)
    {
        if ($this->redis->hExists($hash, $key)){
            return $this->redis->hGet($hash, $key);
        }
        return false;
    }

    public function setHashCache($hash, $key, $value)
    {
        $this->redis->hSet($hash, $key, $value);
    }

    public function pullQueue($key)
    {
        return $this->redis->lPop($key);
    }

    public function rPushQueue($key, $value)
    {
        $this->redis->rPush($key, $value);
    }

    public function setString($key, $value, $ttl)
    {
        $this->redis->setex($key, $ttl, $value);
    }

    public function checkString($key)
    {
        return $this->redis->exists($key);
    }

    public function getString($key)
    {
        if ($this->redis->exists($key)){
            return $this->redis->get($key);
        }
        return false;
    }

    public function delString($key)
    {
        $this->redis->del($key);
    }
}
