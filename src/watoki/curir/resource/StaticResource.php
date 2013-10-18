<?php
namespace watoki\curir\resource;

use watoki\curir\http\MimeTypes;
use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\Resource;

/**
 * A StaticResource is the implicit Resource associated with a static file.
 */
class StaticResource extends Resource {

    /**
     * @param Request $request
     * @return Response
     */
    public function respond(Request $request) {
        $response = new Response();

        $extension = null;
        $pos = strrpos($this->getName(), '.');
        if ($pos) {
            $extension = substr($this->getName(), $pos + 1);
        }

        $file = $this->getDirectory() . DIRECTORY_SEPARATOR . $this->getName();
        $contentType = $extension ? MimeTypes::getType($extension) : $this->getDefaultContentType();

        $response->setBody(file_get_contents($file));
        $response->getHeaders()->set(Response::HEADER_CONTENT_TYPE, $contentType);

        return $response;
    }

    protected function getDefaultContentType() {
        return MimeTypes::getType('txt');
    }
}
 