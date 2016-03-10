<?php
namespace watoki\curir\rendering\locating;

use watoki\curir\delivery\WebRouter;

class ResourceTemplateLocator extends ClassTemplateLocator {

    protected function getName($class) {
        return lcfirst(substr(basename(parent::getName($class)), 0, -strlen(WebRouter::SUFFIX)));
    }

} 