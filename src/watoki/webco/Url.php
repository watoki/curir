<?php
namespace watoki\webco;
 
use watoki\collections\Map;

class Url {

    public static $CLASS = __CLASS__;

    /**
     * @var Path
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

    /**
     * @var null|string
     */
    private $scheme;

    function __construct(Path $resource, Map $parameters = null, $fragment = null, $scheme = null) {
        $this->resource = $resource;
        $this->parameters = $parameters ?: new Map();
        $this->fragment = $fragment;
        $this->scheme = $scheme;
    }

    static public function parse($string) {
        $fragment = null;
        $fragmentPos = strpos($string, '#');
        if ($fragmentPos !== false) {
            $fragment = substr($string, $fragmentPos + 1);
            $string = substr($string, 0, $fragmentPos);
        }

        $scheme = null;
        $schemeSepPos = strpos($string, ':');
        if ($schemeSepPos !== false) {
            $scheme = substr($string, 0, $schemeSepPos);
            $string = substr($string, $schemeSepPos + 1);
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
            }
        }

        return new Url(Path::parse($string), $parameters, $fragment, $scheme);
    }

    public function isRelative() {
        return !$this->resource->isAbsolute() && $this->isSameHost();
    }

    public function toString() {
        $queries = array();
        foreach ($this->flattenParams($this->parameters) as $key => $value) {
            $queries[] = urlencode($key) . '=' . urlencode($value);
        }

        return $this->resource->toString()
            . ($queries ? '?' . implode('&', $queries) : '')
            . ($this->fragment ? '#' . $this->fragment : '');
    }

    public function isSameHost() {
        return $this->resource->toString() != '//';
    }

    /**
     * @return Path
     */
    public function getResource() {
        return $this->resource;
    }

    // TODO This should all be done by Path
    public function getResourceDir() {
        return dirname($this->resource->toString()) . '/';
    }

    public function getResourceBase() {
        return basename($this->resource->toString());
    }

    public function getResourceBaseName() {
        $base = $this->getResourceBase();
        $dotPos = strrpos($base, '.');
        if ($dotPos === false || $dotPos == 0) {
            return $base;
        }
        return substr($base, 0, $dotPos);
    }

    public function getResourceBaseExtension() {
        $base = $this->getResourceBase();
        $dotPos = strrpos($base, '.');
        if ($dotPos === false || strlen($base) == $dotPos + 1) {
            return $base;
        }
        return substr($base, $dotPos + 1);
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

}
