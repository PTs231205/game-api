<?php

class RedisHandler {
    private $redis;
    private $connected = false;

    public function __construct($config) {
        try {
            if (class_exists('Redis')) {
                $this->redis = new Redis();
                $this->connected = $this->redis->connect($config['redis']['host'], $config['redis']['port']);
                if ($config['redis']['password']) {
                    $this->redis->auth($config['redis']['password']);
                }
            }
        } catch (Exception $e) {
            // Log error
            $this->connected = false;
        }
    }

    public function get($key) {
        return $this->connected ? $this->redis->get($key) : null;
    }

    public function set($key, $value, $ttl = 3600) {
        return $this->connected ? $this->redis->setex($key, $ttl, $value) : false;
    }
}
