<?php
namespace watoki\curir;
 
use watoki\collections\Liste;

class Path {

    const SEPARATOR = '/';

    /**
     * @var Liste
     */
    private $nodes;

    function __construct(Liste $nodes = null) {
        $this->nodes = $nodes ?: new Liste();
    }

    public static function parse($string) {
        if (substr($string, -1) == self::SEPARATOR) {
            $string = substr($string, 0, -1);
        }
        return new Path(Liste::split(self::SEPARATOR, $string));
    }

    public function getLeaf() {
        return $this->nodes->last();
    }

    public function getLeafName() {
        $last = $this->getLeaf();
        if (strstr($last, '.') === false) {
            return $last;
        }
        $baseExtension = explode('.', $last);
        return $baseExtension[0];
    }

    public function getLeafExtension() {
        $baseExtension = explode('.', $this->getLeaf());
        return count($baseExtension) == 1 ? null : end($baseExtension);
    }

    public function toString() {
        return $this->nodes->join(self::SEPARATOR);
    }

    /**
     * @return boolean
     */
    public function isAbsolute() {
        return $this->nodes->first() == '';
    }

    /**
     * @return Path
     */
    public function copy() {
        return new Path($this->nodes->copy());
    }

    /**
     * @return \watoki\collections\Liste
     */
    public function getNodes() {
        return $this->nodes;
    }

}
