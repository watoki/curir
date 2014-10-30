<?php
namespace watoki\curir\rendering\locating;

use watoki\curir\delivery\WebRouter;

class ResourceTemplateLocator extends ClassTemplateLocator {

    protected function getName() {
        return lcfirst(substr(basename(parent::getName()), 0, -strlen(WebRouter::SUFFIX)));
    }

} 