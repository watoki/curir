<?php
namespace watoki\curir\serialization;

class InflaterRepository {

    /** @var array|Inflater[] indexed by types */
    private $inflaters = array();

    function __construct() {
        $this->setInflater('boolean', new BooleanInflater());
        $this->setInflater('integer', new IntegerInflater());
        $this->setInflater('float', new FloatInflater());
        $this->setInflater('array', new ArrayInflater());
        $this->setInflater('string', new StringInflater());
        $this->setInflater('DateTime', new DateTimeInflater());
    }

    /**
     * @param $type
     * @throws \Exception
     * @return Inflater
     */
    public function getInflater($type) {
        $originalType = $type;
        $type = $this->normalizeType($type);

        while ($type) {
            $normalized = $this->normalizeType($type);
            foreach ($this->inflaters as $key => $provider) {
                if ($normalized == $key) {
                    return $provider;
                }
            }
            $type = get_parent_class($type);
        }
        throw new \Exception("Could not find inflater for type [$originalType]");
    }

    public function setInflater($type, Inflater $inflater) {
        $this->inflaters[$this->normalizeType($type)] = $inflater;
    }

    private function normalizeType($class) {
        return trim(strtolower($class), '\\');
    }

} 