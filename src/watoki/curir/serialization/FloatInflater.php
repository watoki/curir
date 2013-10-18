<?php
namespace watoki\curir\serialization;

class FloatInflater implements Inflater {

    public function inflate($value) {
        return floatval($value);
    }
}