<?php
namespace watoki\curir;

use watoki\curir\error\HttpError;
use watoki\deli\Request;
use watoki\deli\router\DynamicRouter;
use watoki\deli\router\MultiRouter;
use watoki\deli\router\StaticRouter;
use watoki\deli\Target;
use watoki\deli\target\ObjectTarget;
use watoki\factory\Factory;
use watoki\stores\file\FileStore;

class WebRouter extends MultiRouter {

    const SUFFIX = 'Resource';

    public $dynamicRouter;

    public $staticRouter;

    /** @var Factory */
    private $factory;

    /** @var string */
    private $rootClass;

    /**
     * @param Factory $factory
     * @param string $rootClass
     * @param string|null $rootDirectory
     */
    function __construct(Factory $factory, $rootClass, $rootDirectory = null) {
        if (!$rootDirectory) {
            $reflection = new \ReflectionClass($rootClass);
            $rootDirectory = dirname($reflection->getFileName());
        }
        $store = $factory->getInstance(FileStore::$CLASS, array('rootDirectory' => $rootDirectory));
        $namespace = implode('\\', array_slice(explode('\\', $rootClass), 0, -1));

        $this->factory = $factory;
        $this->rootClass = $rootClass;

        $this->dynamicRouter = new DynamicRouter();
        $this->staticRouter = new StaticRouter($factory, $store, $namespace, self::SUFFIX);

        $this->add($this->dynamicRouter);
        $this->add($this->staticRouter);
    }

    public function route(Request $request) {
        if ($request->getTarget()->isEmpty()) {
            $object = $this->factory->getInstance($this->rootClass);
            return new ObjectTarget($request, $object, $this->factory);
        }
        try {
            return parent::route($request);
        } catch (\Exception $e) {
            throw new HttpError(WebResponse::STATUS_NOT_FOUND,
                "The resource [{$request->getTarget()}] does not exist in [{$request->getContext()}]",
                null, 0, $e);
        }
    }

} 