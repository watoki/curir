<?php
namespace watoki\webco;
 
use watoki\collections\Map;

class Url {

    public static $CLASS = __CLASS__;

    /**
     * @var string
     */
    private $resource;

    /**
     * @var \watoki\collections\Map
     */
    private $parameters;

    /**
     * @var string|null
     */
    private $fragment;

    function __construct($resource, Map $parameters = null, $fragment = null) {
        $this->resource = $resource;
        $this->parameters = $parameters ?: new Map();
        $this->fragment = $fragment;
    }

    static public function parse($string) {
        $fragment = null;
        $fragmentPos = strpos($string, '#');
        if ($fragmentPos !== false) {
            $fragment = substr($string, $fragmentPos + 1);
            $string = substr($string, 0, $fragmentPos);
        }

        $parameters = new Map();
        $queryPos = strpos($string, '?');
        if ($queryPos !== false) {
            $query = substr($string, $queryPos + 1);
            $string = substr($string, 0, $queryPos);

            if ($query) {
                foreach (explode('&', $query) as $pair) {
                    if (strstr($pair, '=') !== false) {
                        list($key, $value) = explode('=', $pair);
                    } else {
                        $key = $pair;
                        $value = null;
                    }
                    $parameters->set($key, $value);
                }
            }
        }

        return new Url($string, $parameters, $fragment);
    }

    public function isRelative() {
        return substr($this->resource, 0, 1) != '/' && $this->isSameHost();
    }

    public function toString() {
        $queries = array();
        foreach ($this->parameters as $key => $value) {
            $queries[] = urlencode($key) . '=' . urlencode($value);
        }

        return $this->resource
            . ($queries ? '?' . implode('&', $queries) : '')
            . ($this->fragment ? '#' . $this->fragment : '');
    }

    function __toString() {
        return $this->toString();
    }

    public function isSameHost() {
        return !preg_match('#^([^:/]+:)?//#', $this->resource);
    }

    /**
     * @return string
     */
    public function getResource() {
        return $this->resource;
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

}
