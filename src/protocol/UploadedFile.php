<?php
namespace watoki\curir\protocol;

class UploadedFile {

    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var string */
    private $tmpName;

    /** @var int */
    private $error;

    /** @var int */
    private $size;

    function __construct($name, $type, $tmpName, $error, $size) {
        $this->error = (int)$error;
        $this->name = $name;
        $this->size = (int)$size;
        $this->tmpName = $tmpName;
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getError() {
        return $this->error;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * @return string
     */
    public function getTemporaryName() {
        return $this->tmpName;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

} 