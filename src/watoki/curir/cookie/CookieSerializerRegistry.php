<?php
namespace watoki\curir\cookie;

use watoki\stores\file\FileSerializerRegistry;

class CookieSerializerRegistry extends FileSerializerRegistry {

    protected function registerDefaultSerializers() {
        parent::registerDefaultSerializers();
        $this->register(Cookie::$CLASS, new CookieSerializer());
    }

} 