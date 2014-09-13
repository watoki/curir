<?php
namespace watoki\curir;

use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\error\HttpError;
use watoki\curir\protocol\MimeTypes;
use watoki\deli\Request;
use watoki\deli\router\StaticRouter;
use watoki\deli\target\CallbackTarget;
use watoki\deli\Target;
use watoki\deli\target\ObjectTarget;
use watoki\factory\Factory;
use watoki\stores\file\FileStore;
use watoki\stores\file\raw\File;

class WebRouter extends StaticRouter {

    const SUFFIX = 'Resource';

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

        parent::__construct($factory, $store, $namespace, self::SUFFIX);

        $this->factory = $factory;
        $this->rootClass = $rootClass;

        $this->setFileTargetCreator(function (WebRequest $request, File $file) {
            return CallbackTarget::factory(function (WebRequest $request) use ($file) {
                $response = new WebResponse($file->content);

                if (strpos($file->id, '.') !== false) {
                    $parts = explode('.', $file->id);
                    $response->getHeaders()->set(WebResponse::HEADER_CONTENT_TYPE, MimeTypes::getType(end($parts)));
                } else if (!$request->getFormats()->isEmpty()) {
                    $response->getHeaders()->set(WebResponse::HEADER_CONTENT_TYPE, MimeTypes::getType($request->getFormats()->first()));
                }
                return $response;
            })->create($request);
        });
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

    /**
     * @param Request|WebRequest $request
     * @return null|string
     */
    protected function existingFile(Request $request) {
        foreach ($request->getFormats() as $format) {
            $file = $request->getTarget()->toString() . '.' . $format;
            if ($this->store->exists($file)) {
                return $file;
            }
        }
        return parent::existingFile($request);
    }

} 