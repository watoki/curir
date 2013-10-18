<?php
namespace watoki\curir\serialization;

class ArrayInflater implements Inflater {

    public function inflate($value) {
        return (array) $value;
    }
}