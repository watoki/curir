<?php
namespace watoki\webco;
 
use watoki\collections\Liste;

class Path extends Liste {

    public static function parse($string) {
        if (substr($string, -1) == '/') {
            $string = substr($string, 0, -1);
        }
        return new Path(explode('/', $string));
    }

    public function getLeaf() {
        return $this->last();
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
        return $this->join('/');
    }

    /**
     * @return boolean
     */
    public function isAbsolute() {
        return $this->first() == '';
    }

    /**
     * @return Path
     */
    public function copy() {
        return parent::copy();
    }

}
