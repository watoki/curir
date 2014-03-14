<?php
namespace watoki\curir\responder;

use watoki\curir\http\MimeTypes;
use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\Responder;

class DefaultResponder extends Responder {

    /** @var array|string[] indexed by format */
    private $bodies = array();

    /**
     * @param string|array $body Array of bodies indexed by format (e.g. ['html' => '<b>Hello</b>', 'txt' => 'Hello', '' => 'Default'])
     */
    function __construct($body = '') {
        if (is_array($body)) {
            $this->bodies = $body;
        } else {
            $this->bodies[''] = $body;
        }
    }

    public function setBody($format, $body) {
        $this->bodies[$format] = $body;
    }

    public function getBody($format) {
        return $this->bodies[$format];
    }

    /**
     * @param \watoki\curir\http\Request $request
     * @return \watoki\curir\http\Response
     */
    public function createResponse(Request $request) {
        $format = null;
        $body = isset($this->bodies['']) ? $this->bodies[''] : '';

        foreach ($request->getFormats() as $accepted) {
            if (array_key_exists($accepted, $this->bodies)) {
                $body = $this->bodies[$accepted];
                break;
            }
        }

        $response = new Response($body);
        if ($format) {
            $response->getHeaders()->set(Response::HEADER_CONTENT_TYPE, MimeTypes::getType($format));
        }

        return $response;
    }
}