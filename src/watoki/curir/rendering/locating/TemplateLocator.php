<?php
namespace watoki\curir\rendering\locating;

interface TemplateLocator {

    /**
     * @param string $format The format of the template
     * @return string
     * @throws \Exception If the template cannot be found in given format
     */
    public function find($format);

} 