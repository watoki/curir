<?php
namespace watoki\curir\cookie;

use watoki\stores\Serializer;
use watoki\stores\Store;

class CookieStore extends Store {

    public static $CLASS = __CLASS__;

    /** @var array With cookie data indexed by key */
    private $source;

    /** @var array|array[] setcookie arguments without key indexed by key */
    private $sink = array();

    /**
     * @param SerializerRepository $serializers <-
     * @param array $source
     */
    public function __construct(SerializerRepository $serializers, array $source) {
        parent::__construct(Cookie::$CLASS, $serializers);
        $this->source = $source;
    }

    /**
     * @param string $key
     * @return Cookie Entity belonging given key
     * @throws \Exception If no entity with given key exists
     */
    public function read($key) {
        if (!array_key_exists($key, $this->source)) {
            throw new \Exception("Cookie with name [$key] does not exist");
        }
        return $this->inflate($this->source[$key], $key);
    }

    /**
     * @param Cookie $entity
     * @param string $key If omitted, a key will be generated
     * @throws \InvalidArgumentException If no key is provided
     * @return null
     */
    public function create($entity, $key = null) {
        if (!$key) {
            throw new \InvalidArgumentException('Cookie key cannot be empty.');
        }
        $this->sink[$key] = $this->serialize($entity, $key);
    }

    /**
     * @param Cookie $entity
     * @throws \BadMethodCallException always
     * @return null
     */
    public function update($entity) {
        $this->create($entity, $this->getKey($entity));
    }

    /**
     * @param Cookie $entity
     * @return null
     */
    public function delete($entity) {
        $key = $this->getKey($entity);
        $this->sink[$key] = array(null, time() - 3600, $entity->path);
        $this->removeKey($key);
    }

    /**
     * @return array|mixed[] All stored keys
     */
    public function keys() {
        return array_keys($this->source);
    }

    /**
     * @return Serializer
     */
    protected function createEntitySerializer() {
        return new CookieSerializer();
    }

    /**
     * @param callable $callable Called with (key, payload, expire, path, domain, secure, httpOnly) for each cookie
     */
    public function applyCookies($callable) {
        foreach ($this->sink as $key => $args) {
            call_user_func_array($callable, array_merge(array($key), $args));
        }
    }
}