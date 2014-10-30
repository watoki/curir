<?php
namespace watoki\curir;

use watoki\curir\delivery\WebRequest;
use watoki\curir\delivery\WebResponse;
use watoki\curir\rendering\Renderer;
use watoki\curir\rendering\locating\ResourceTemplateLocator;
use watoki\curir\responder\FormatResponder;
use watoki\factory\Factory;

class Resource {

    /** @var Factory */
    protected $factory;

    /**
     * @param Factory $factory <-
     */
    function __construct(Factory $factory) {
        $this->factory = $factory;
    }

    /**
     * @param WebRequest $request
     * @return WebRequest|null
     */
    public function before(WebRequest $request) {
        return $request;
    }

    /**
     * @param Responder|string|array $return
     * @param WebRequest $request
     * @return WebResponse|null
     */
    public function after($return, WebRequest $request) {
        if ($return instanceof WebResponse) {
            return $return;
        } else if ($return instanceof Responder) {
            return $return->createResponse($request);
        } else if (is_array($return)) {
            return $this->createDefaultResponder($return)->createResponse($request);
        } else {
            return $this->createResponse($return);
        }
    }

    /**
     * @param $return
     * @return Responder
     */
    protected function createDefaultResponder($return) {
        $locator = new ResourceTemplateLocator($this);
        $renderer = $this->createDefaultRenderer();

        return new FormatResponder($return, $locator, $renderer);
    }

    /**
     * @return Renderer
     */
    protected function createDefaultRenderer() {
        return $this->factory->getInstance(Renderer::RENDERER);
    }

    /**
     * @param mixed $return
     * @throws \Exception if $return cannot be converted to string
     * @return WebResponse
     */
    protected function createResponse($return) {
        if (is_object($return) && !method_exists($return, '__toString')) {
            throw new \Exception("Cannot convert to string: " . get_class($return));
        }
        return new WebResponse((string) $return);
    }

}