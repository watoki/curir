<?php
namespace watoki\curir\resource;

use watoki\curir\http\MimeTypes;
use watoki\curir\http\Request;
use watoki\curir\Resource;
use watoki\curir\http\Response;

/**
 * A StaticResource is the implicit Resource associated with a static file.
 */
class StaticResource extends Resource {

    /** @var string */
    private $file;

    public function __construct($name, Container $parent = null, $file) {
        parent::__construct($name, $parent);
        $this->file = $file;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function respond(Request $request) {
        $response = new Response();

        $extension = 'txt';
        $basename = basename($this->file);
        $pos = strrpos($basename, '.');
        if ($pos) {
            $extension = substr($basename, $pos + 1);
        }

        $response->setBody(file_get_contents($this->file));
        $response->getHeaders()->set(Response::HEADER_CONTENT_TYPE, MimeTypes::getType($extension));

        return $response;
    }
}
 