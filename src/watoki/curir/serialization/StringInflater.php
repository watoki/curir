<?php
namespace watoki\curir\serialization;

class StringInflater implements Inflater {

    public function inflate($value) {
        return strval($value);
    }
}