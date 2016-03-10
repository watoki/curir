<?php
namespace watoki\curir\protocol;

use watoki\collections\Collection;
use watoki\collections\Map;
use watoki\deli\Path;

class Url extends Path {

    const HOST_PREFIX = '//';
    const PORT_SEPARATOR = ':';
    const SCHEME_SEPARATOR = ':';
    const QUERY_STRING_SEPARATOR = '?';
    const FRAGMENT_SEPARATOR = '#';
    const MAX_PARAM_LENGTH = 512;

    /** @var null|string */
    private $scheme;

    /** @var null|string */
    private $host;

    /** @var null|int */
    private $port;

    /** @var \watoki\collections\Map */
    private $parameters;

    /** @var string|null */
    private $fragment;

    /**
     * @param string $scheme
     * @param string $host
     * @param int $port
     * @param Path $path
     * @param Map $parameters
     * @param string|null $fragment
     */
    function __construct($scheme, $host, $port = 80, Path $path = null, Map $parameters = null, $fragment = null) {
        parent::__construct($path ?: new Path());
        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->parameters = $parameters ?: new Map();
        $this->fragment = $fragment;
    }

    /**
     * @return null|string
     */
    public function getScheme() {
        return $this->scheme;
    }

    /**
     * @return null|string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @return int|null
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @return \watoki\collections\Map
     */
    public function getParameters() {
        return $this->parameters->copy();
    }

    /**
     * @param Map $parameters
     * @return static
     */
    public function withParameters(Map $parameters) {
        $newUrl = $this->copy();
        $newUrl->parameters = $parameters;
        return $newUrl;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function withParameter($key, $value) {
        $newUrl = $this->copy();
        $newUrl->parameters->set($key, $value);
        return $newUrl;
    }

    /**
     * @return null|string
     */
    public function getFragment() {
        return $this->fragment;
    }

    /**
     * @return Path
     */
    public function getPath() {
        return new Path($this);
    }

    /**
     * @param Path $path
     * @return static
     */
    public function withPath(Path $path) {
        return $this->with($path);
    }

    static public function fromString($string) {
        if (!$string) {
            return new Url(null, null, null, new Path());
        }

        $fragment = null;
        $fragmentPos = strpos($string, self::FRAGMENT_SEPARATOR);
        if ($fragmentPos !== false) {
            $fragment = substr($string, $fragmentPos + 1);
            $string = substr($string, 0, $fragmentPos);
        }

        $parameters = new Map();
        $queryPos = strpos($string, self::QUERY_STRING_SEPARATOR);
        if ($queryPos !== false) {
            $query = substr($string, $queryPos + 1);
            $string = substr($string, 0, $queryPos);

            if ($query) {
                $parameters = self::parseParameters($query);
            }
        }

        $scheme = null;
        $schemeSepPos = strpos($string, self::SCHEME_SEPARATOR . self::HOST_PREFIX);
        if ($schemeSepPos !== false) {
            $scheme = substr($string, 0, $schemeSepPos);
            $string = substr($string, $schemeSepPos + 1);
        }

        $host = null;
        $port = null;
        if (substr($string, 0, 2) == self::HOST_PREFIX) {
            $string = substr($string, 2);
            $hostPos = strpos($string, Path::SEPARATOR) ?: strlen($string);
            $host = substr($string, 0, $hostPos);
            $string = substr($string, $hostPos);

            $portPos = strpos($host, self::PORT_SEPARATOR);
            if ($portPos !== false) {
                $port = intval(substr($host, $portPos + 1));
                $host = substr($host, 0, $portPos);
            }
        }

        if (!$host && !$string) {
            $path = new Path();
        } else {
            $path = Path::fromString($string);
            if ($path->isEmpty()) {
                $path = new Path(array(''));
            }
        }

        return new Url($scheme, $host, $port, $path, $parameters, $fragment);
    }

    /**
     * @param $query
     * @return Map
     */
    private static function parseParameters($query) {
        $parameters = new Map();
        foreach (explode('&', $query) as $pair) {
            if (strstr($pair, '=') !== false) {
                list($key, $value) = explode('=', $pair);
                $value = urldecode($value);
            } else {
                $key = $pair;
                $value = null;
            }
            if (preg_match('#\[.+\]#', $key)) {
                $paramsMap = $parameters;
                $mapKeys = explode('[', $key);
                foreach ($mapKeys as $mapKey) {
                    if ($mapKey == end($mapKeys)) {
                        $paramsMap->set(trim($mapKey, ']'), $value);
                    } else {
                        $mapKey = trim($mapKey, ']');
                        if (!$paramsMap->has($mapKey)) {
                            $paramsMap->set($mapKey, new Map());
                        }
                        $paramsMap = $paramsMap->get($mapKey);
                    }
                }
            } else {
                $parameters->set($key, $value);
            }
        }
        return $parameters;
    }

    public function toString() {
        $queries = array();
        foreach ($this->flattenParams($this->parameters) as $key => $value) {
            $queries[] = $key . '=' . urlencode($value);
        }

        $port = $this->port ? self::PORT_SEPARATOR . $this->port : '';
        $scheme = $this->scheme ? $this->scheme . self::SCHEME_SEPARATOR : '';

        $server = $this->host && $this->isAbsolute() ? $scheme . self::HOST_PREFIX . $this->host . $port : '';
        return
            $server
            . parent::toString()
            . ($queries ? self::QUERY_STRING_SEPARATOR . implode('&', $queries) : '')
            . ($this->fragment ? self::FRAGMENT_SEPARATOR . $this->fragment : '');
    }

    private function flattenParams($parameters, $i = 0) {
        $flat = new Map();
        foreach ($parameters as $key => $value) {
            if ($value instanceof Collection) {
                foreach ($this->flattenParams($value, $i + 1) as $subKey => $subValue) {
                    $flatKey = $i ? "{$key}][{$subKey}" : "{$key}[{$subKey}]";
                    $this->set($flat, $flatKey, $subValue);
                }
            } else {
                $this->set($flat, $key, $value);
            }
        }
        return $flat;
    }

    private function set(Map $map, $key, $value) {
        $cabBeCasted = !is_object($value) || method_exists($value, '__toString');

        if ($cabBeCasted && strlen((string)$value) <= self::MAX_PARAM_LENGTH) {
            $map->set($key, $value);
        }
    }

    /**
     * @return static
     */
    protected function copy() {
        return new Url($this->scheme, $this->host, $this->port, new Path($this->elements), $this->parameters->deepCopy(), $this->fragment);
    }

}
