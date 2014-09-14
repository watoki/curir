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
use watoki\stores\file\raw\File;
use watoki\stores\file\raw\RawFileStore;

class WebRouter extends StaticRouter {

    const SUFFIX = 'Resource';

    /** @var Factory */
    private $factory;

    /** @var Resource */
    private $root;

    /**
     * @param Factory $factory
     * @param $root
     */
    function __construct(Factory $factory, Resource $root) {
        $rootName = lcfirst($root->getName());

        $directory = $root->getDirectory() . '/' . $rootName;
        $store = $factory->getInstance(RawFileStore::$CLASS, array('rootDirectory' => $directory));
        $class = new \ReflectionClass($root);
        $namespace = $class->getNamespaceName() . '\\' . $rootName;

        parent::__construct($factory, $store, $namespace, self::SUFFIX);

        $this->factory = $factory;
        $this->root = $root;

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
            return new ObjectTarget($request, $this->root, $this->factory);
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