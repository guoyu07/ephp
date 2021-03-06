<?php
namespace ePHP\Cache;

use ePHP\Core\Config;

/**
 * Redis 驱动缓存
 * 需安装: https://github.com/phpredis/phpredis 扩展
 * 或predis: https://github.com/nrk/predis
 */
class CacheRedis
{
    /**
     * Redis connection
     *
     * @var object
     */
    public $connection;

    function __construct($type='redis')
    {
        $config = Config::get('cache_redis');

        if (empty($config)) {
            throw_error("please config cache_redis in conf/main.conf");
        }

        if ($type === 'redis') {
            // C extension - phpredis
            $this->connection = new \Redis();
            $this->connection->connect($config['host'], $config['port'], !empty($config['timeout']) ? $config['timeout'] : 2.5);

            if (!empty($config['auth'])) {
                $this->connection->auth($config['auth']);
            }
        } else {
            // PHP extension - predis
            if (!empty($config['auth'])) {
                $config['password'] = $config['auth'];
            }

            if (empty($config['scheme'])) {
                $config['scheme'] = 'tcp';
            }

            $this->connection = new \Predis\Client($config);
        }
    }

    /**
     * 写缓存
     *
     * @param $key 缓存名称
     * @param $value 缓存内容
     * @param $expire 缓存有效期，0:长期有效, -1: 不缓存
     * @return int
     */
    function set($key, $value, $expire = 0)
    {
        // 如果是对象，需要进行序列号存储
        if (is_array($value) || is_object($value)) {
            $value = serialize($value);
        }

        if ($expire > 0) {
            return $this->connection->setex($key, $expire, $value);
        } else {
            return $this->connection->set($key, $value);
        }
    }

    /**
     * 取缓存
     *
     * @param string $key 缓存名称
     * @return mixed
     */
    function get($key)
    {
        $data = $this->connection->get($key);
        $dataType = substr($data, 0, 2);

        // 如果是array或object需要反序列化
        if ($dataType === 'a:' || $dataType === 'O:') {
            $data = unserialize($data);
        }

        return $data;
    }

    /**
     * 删除缓存
     *
     * @param string $key 缓存名称
     * @return bool
     */
    function delete($key)
    {
        return $this->connection->delete($key);
    }

    /**
     * 刷新所有的缓存
     *
     * @return booean
     */
    public function flush()
    {
        // return $this->connection->flushAll();
        return $this->connection->flushDb();
    }

    // function __destruct()
    // {
    //     $this->connection->close();
    // }
}
