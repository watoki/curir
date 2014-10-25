<?php
namespace watoki\curir\rendering;

interface Locatable {

    /**
     * @return string Name of class with which it can be found (possibly omitting pre/suffixes)
     */
    public function getName();

    /**
     * @return string Directory of class
     */
    public function getDirectory();

} 