<?php
namespace watoki\curir\serialization;

class DateTimeInflater implements Inflater {

    public function inflate($value) {
        return new \DateTime($value);
    }
}