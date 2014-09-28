<?php
namespace watoki\curir\cookie;

use watoki\stores\Serializer;
use watoki\stores\Store;

class CookieStore extends Store {

    public static $CLASS = __CLASS__;

    /** @var array With cookie data indexed by key */
    private $source;

    /** @var array|array[] setcookie arguments without name indexed by name */
    private $serialized = array();

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
        $value = $this->source[$key];
        if (!json_decode($value)) {
            return $this->inflate(json_encode(array("payload" => $value)), $key);
        }
        return $this->inflate($value, $key);
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
        $this->serialized[$key] = $this->serialize($entity, $key);
    }

    /**
     * @param Cookie $cookie
     * @param string $key
     * @return array
     */
    protected function serialize($cookie, $key) {
        return array(
                parent::serialize($cookie, $key),
                $cookie->expire ? $cookie->expire->getTimestamp() : null,
                $cookie->path,
                $cookie->domain,
                $cookie->secure,
                $cookie->httpOnly);
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
        $this->serialized[$key] = array(null, time() - 3600, $entity->path);
        $this->removeKey($key);
    }

    /**
     * @return array|mixed[] All stored keys
     */
    public function keys() {
        return array_keys(array_merge($this->source, $this->serialized));
    }

    /**
     * @return Serializer
     */
    protected function createEntitySerializer() {
        return new CookieSerializer($this->getEntityClass(), $this->getSerializers());
    }

    /**
     * @param callable $callable Called with (key, payload, expire, path, domain, secure, httpOnly) for each cookie
     */
    public function applyCookies($callable) {
        foreach ($this->serialized as $key => $args) {
            call_user_func_array($callable, array_merge(array($key), $args));
        }
    }
}