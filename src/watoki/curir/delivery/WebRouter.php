<?php
namespace watoki\curir\delivery;

use watoki\deli\Path;
use watoki\deli\Request;
use watoki\deli\router\StaticRouter;
use watoki\deli\router\TargetNotFoundException;
use watoki\deli\Target;
use watoki\deli\target\TargetFactory;
use watoki\factory\Factory;
use watoki\stores\file\raw\RawFileStore;

class WebRouter extends StaticRouter {

    const SUFFIX = 'Resource';

    /** @var TargetFactory|null */
    private $default;

    /** @var bool */
    private $useFirstIndex = true;

    /**
     * @param Factory $factory <-
     * @param string $directory
     * @param string $namespace
     * @param string $suffix
     */
    function __construct(Factory $factory, $directory, $namespace, $suffix = self::SUFFIX) {
        $store = $factory->getInstance(RawFileStore::$CLASS, array('rootDirectory' => $directory));
        parent::__construct($factory, $store, $namespace, $suffix);
    }

    public function setDefaultTarget(TargetFactory $default) {
        $this->default = $default;
    }

    public function route(Request $request) {
        if ($this->default && $request->getTarget()->isEmpty()) {
            return $this->default->create($request);
        }

        try {
            return parent::route($request);
        } catch (TargetNotFoundException $e) {
            if ($request instanceof WebRequest && $found = $this->findFile($request)) {
                return $this->createTargetFromFile($request, $found);
            }
            throw $e;
        }
    }

    public function setUseFirstIndex($to) {
        $this->useFirstIndex = $to;
    }

    protected function findIndexNode(Request $request, Path $currentContext) {
        if (!$this->useFirstIndex && $currentContext->isEmpty()) {
            return null;
        }
        return parent::findIndexNode($request, $currentContext);
    }

    private function findFile(WebRequest $request) {
        foreach ($request->getFormats() as $format) {
            $found = $this->findExistingFile($request, $request->getTarget()->toString(), $format);
            if ($found) {
                return $found;
            }
        }
        return $this->findExistingFile($request, $request->getTarget()->toString(), null);
    }

    private function findExistingFile(WebRequest $request, $target, $format) {
        $suffix = $format ? '.' . $format : '';
        if (substr($target, -1) == '/') {
            $target .= $this->index;
        }
        if ($this->store->exists($target . $suffix, $request)) {
            return $target . $suffix;
        }
        return null;
    }

    private function createTargetFromFile(WebRequest $request, $file) {
        $nextRequest = $request->withContext($request->getTarget())->withTarget(new Path());

        return new FileTarget($nextRequest, $this->store->read($file), $file);
    }

} 