<?php
namespace watoki\curir;

use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\error\HttpError;
use watoki\curir\protocol\MimeTypes;
use watoki\deli\Request;
use watoki\deli\router\StaticRouter;
use watoki\deli\router\TargetNotFoundException;
use watoki\deli\target\CallbackTarget;
use watoki\deli\target\TargetFactory;
use watoki\deli\Target;
use watoki\factory\Factory;
use watoki\stores\file\raw\File;
use watoki\stores\file\raw\RawFileStore;

class WebRouter extends StaticRouter {

    const SUFFIX = 'Resource';

    /** @var Factory */
    private $factory;

    /** @var TargetFactory|null */
    private $default;

    /**
     * @param Factory $factory <-
     * @param string $directory
     * @param string $namespace
     * @param string $suffix
     * @internal param $root
     */
    function __construct(Factory $factory, $directory, $namespace, $suffix = self::SUFFIX) {
        parent::__construct($factory, $factory->getInstance(RawFileStore::$CLASS, array('rootDirectory' => $directory)), $namespace, $suffix);

        $this->factory = $factory;

        $this->setFileTargetCreator(function (WebRequest $request, File $file, $fileKey) {
            return CallbackTarget::factory(function (WebRequest $request) use ($file, $fileKey) {
                $response = new WebResponse($file->content);

                if (strpos($fileKey, '.') !== false) {
                    $parts = explode('.', $fileKey);
                    $response->getHeaders()->set(WebResponse::HEADER_CONTENT_TYPE, MimeTypes::getType(end($parts)));
                } else if (!$request->getFormats()->isEmpty()) {
                    $response->getHeaders()->set(WebResponse::HEADER_CONTENT_TYPE, MimeTypes::getType($request->getFormats()->first()));
                }
                return $response;
            })->create($request);
        });
    }

    public function route(Request $request) {
        if ($this->default && $request->getTarget()->isEmpty()) {
            return $this->default->create($request);
        }
        try {
            return parent::route($request);
        } catch (TargetNotFoundException $e) {
            throw new HttpError(WebResponse::STATUS_NOT_FOUND,
                    "The resource [{$request->getTarget()}] does not exist in [{$request->getContext()}]",
                    null, 0, $e);
        }
    }

    public function setDefaultTarget(TargetFactory $default) {
        $this->default = $default;
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