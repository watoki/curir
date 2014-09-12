<?php
namespace watoki\curir;
 
use watoki\collections\Map;
use watoki\deli\Path;

class Url extends Path {

    public static $CLASS = __CLASS__;

    const HOST_PREFIX = '//';

    const PORT_SEPARATOR = ':';

    const SCHEME_SEPARATOR = ':';

    const QUERY_STRING_SEPARATOR = '?';

    const FRAGMENT_SEPARATOR = '#';

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

    function __construct($scheme, $host, $port, Path $path, Map $parameters = null, $fragment = null) {
        parent::__construct($path->toArray());
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
     * @param null|string $scheme
     */
    public function setScheme($scheme) {
        $this->scheme = $scheme;
    }

    /**
     * @return null|string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param null|string $host
     */
    public function setHost($host) {
        $this->host = $host;
    }

    /**
     * @return int|null
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @param int|null $port
     */
    public function setPort($port) {
        $this->port = $port;
    }

    /**
     * @return \watoki\collections\Map
     */
    public function getParameters() {
        return $this->parameters;
    }

    /**
     * @return null|string
     */
    public function getFragment() {
        return $this->fragment;
    }

    public function setFragment($fragment) {
        $this->fragment = $fragment;
    }

    static public function fromString($string) {
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
            $hostPos = strpos($string, self::SEPARATOR) ?: strlen($string);
            $host = substr($string, 0, $hostPos);
            $string = substr($string, $hostPos);

            $portPos = strpos($host, self::PORT_SEPARATOR);
            if ($portPos !== false) {
                $port = intval(substr($host, $portPos + 1));
                $host = substr($host, 0, $portPos);
            }
        }

        $path = Path::fromString($string);
        if ($path->isEmpty()) {
            $path->append('');
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

        $isAbsolutePath = $this->isEmpty() || $this->first() == '';
        return ($this->host && $isAbsolutePath ? $scheme . self::HOST_PREFIX . $this->host . $port : '')
                . parent::toString()
                . ($queries ? self::QUERY_STRING_SEPARATOR . implode('&', $queries) : '')
                . ($this->fragment ? self::FRAGMENT_SEPARATOR . $this->fragment : '');
    }

    public function getPath() {
        return new Path($this->elements);
    }

    private function flattenParams(Map $parameters, $i = 0) {
        $flat = new Map();
        foreach ($parameters as $key => $value) {
            if ($value instanceof Map) {
                foreach ($this->flattenParams($value, $i+1) as $subKey => $subValue) {
                    $flatKey = $i ? "{$key}][{$subKey}" : "{$key}[{$subKey}]";
                    $flat->set($flatKey, $subValue);
                }
            } else {
                $flat->set($key, $value);
            }
        }
        return $flat;
    }

    /**
     * @return static
     */
    public function copy() {
        return new Url($this->scheme, $this->host, $this->port, parent::copy(), $this->parameters->deepCopy(), $this->fragment);
    }

}
