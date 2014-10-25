<?php
namespace watoki\curir\rendering;

class NoneTemplateLocator implements TemplateLocator {

    /** @var string */
    private $template;

    /**
     * @param string $template The template
     */
    function __construct($template) {
        $this->template = $template;
    }

    /**
     * @param string $format The format of the template
     * @return string
     * @throws \Exception If the template cannot be found in given format
     */
    public function find($format) {
        return $this->template;
    }
}