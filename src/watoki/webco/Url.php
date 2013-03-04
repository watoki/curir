<?php
namespace watoki\webco;
 
use watoki\collections\Map;

class Url {

    public static $CLASS = __CLASS__;

    private $resource;

    private $parameters;

    private $fragment;

    function __construct($resource, Map $parameters = null, $fragment = null) {
        $this->resource = $resource;
        $this->parameters = $parameters ?: new Map();
        $this->fragment = $fragment;
    }

    public function isRelative() {
        return substr($this->resource, 0, 1) != '/' && !preg_match('#^[^:/]+://#', $this->resource);
    }

    public function toString() {
        $queries = array();
        foreach ($this->parameters as $key => $value) {
            $queries[] = $key . '=' . urlencode($value);
        }

        return $this->resource
            . ($queries ? '?' . implode('&', $queries) : '')
            . ($this->fragment ? '#' . $this->fragment : '');
    }

    function __toString() {
        return $this->toString();
    }

}
