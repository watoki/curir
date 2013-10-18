<?php
namespace watoki\curir\serialization;

class IntegerInflater implements Inflater {

    public function inflate($value) {
        return intval($value);
    }
}