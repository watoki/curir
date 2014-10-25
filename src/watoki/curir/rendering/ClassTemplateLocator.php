<?php
namespace watoki\curir\rendering;

use watoki\factory\Factory;
use watoki\stores\file\raw\RawFileStore;

class ClassTemplateLocator implements TemplateLocator {

    /** @var Locatable */
    private $object;

    /** @var RawFileStore */
    private $store;

    public function __construct(Locatable $object, Factory $factory) {
        $this->object = $object;
        $this->store = $factory->getInstance(RawFileStore::$CLASS, array(
                'rootDirectory' => $object->getDirectory(),
        ));
    }

    public function find($format) {
        $templateFile = $this->object->getName() . '.' . $format;

        if (!$this->store->exists($templateFile)) {
            $class = get_class($this->object);
            throw new \Exception("Could not find template [$templateFile] for [$class]");
        }
        return $this->store->read($templateFile)->content;
    }

} 