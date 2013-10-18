<?php
namespace watoki\curir\serialization;

class BooleanInflater implements Inflater {

    public function inflate($value) {
        return strtolower($value) == 'false' ? false : !!$value;
    }
}