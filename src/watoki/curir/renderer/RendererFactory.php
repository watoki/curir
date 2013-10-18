<?php
namespace watoki\curir\renderer;

use watoki\curir\Renderer;

class RendererFactory {

    public static $CLASS = __CLASS__;

    /** @var array|Renderer[] */
    private $renderers = array();

    function __construct() {
        $this->setDefaultRenderers();
    }

    protected function setDefaultRenderers() {
        $this->setRenderer('json', new JsonRenderer());
        $this->setRenderer('none', new NoneRenderer());
    }

    /**
     * @param string $format
     * @throws \Exception If no renderer for given format is set
     * @return Renderer
     */
    public function getRenderer($format) {
        if (!array_key_exists($format, $this->renderers)) {
            throw new \Exception("Could not render model. No Renderer set for format [$format].");
        }

        $this->renderers[$format];
    }

    /**
     * @param string $format
     * @param Renderer $renderer
     */
    public function setRenderer($format, $renderer) {
        $this->renderers[$format] = $renderer;
    }

}