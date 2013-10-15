<?php
namespace watoki\curir\http;
 
use watoki\collections\Liste;

class Path extends Liste {

    const SEPARATOR = '/';

    public static function parse($string) {
        if (substr($string, -1) == self::SEPARATOR) {
            $string = substr($string, 0, -1);
        }
        return new Path(Liste::split(self::SEPARATOR, $string)->elements);
    }

    public function toString() {
        return $this->join(self::SEPARATOR);
    }

}
