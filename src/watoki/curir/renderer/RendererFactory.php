<?php
namespace watoki\curir\renderer;

use watoki\curir\Renderer;
use watoki\curir\controller\Component;
use watoki\curir\router\FileRouter;
use watoki\factory\Factory;

class RendererFactory {

    public static $CLASS = __CLASS__;

    /**
     * @var \watoki\factory\Factory
     */
    private $factory;

    private $renderers = array();

    private $contentTypes = array();

    function __construct(Factory $factory) {
        $this->factory = $factory;

        $this->setDefaultRenderers();
    }

    /**
     * @param Component $component
     * @param string $format
     * @throws \Exception If no renderer for given format is set
     * @return Renderer
     */
    public function getRenderer(Component $component, $format) {
        if (!array_key_exists($format, $this->renderers)) {
            throw new \Exception("Could not render model. No renderer set for format [$format].");
        }

        return $this->factory->getInstance($this->renderers[$format], array(
            'template' => $this->getTemplate($component, $format)
        ));
    }

    public function getContentType($format) {
        if (!array_key_exists($format, $this->contentTypes)) {
            throw new \Exception("No content type set for format [$format].");
        }

        return $this->contentTypes[$format];
    }

    /**
     * @param string $format
     * @param string $rendererClass
     * @param string $contentType
     */
    public function setRenderer($format, $rendererClass, $contentType = 'text/plain') {
        $this->renderers[$format] = $rendererClass;
        $this->contentTypes[$format] = $contentType;
    }

    protected function setDefaultRenderers() {
        $this->setRenderer('json', JsonRenderer::$CLASS, 'text/json');
        $this->setRenderer('none', NoneRenderer::$CLASS, 'text/plain');
    }

    /**
     * @param Component $component
     * @param $format
     * @return null|string
     */
    protected function getTemplate(Component $component, $format) {
        $reflection = new \ReflectionClass($component);
        do {
            $templateFile = $this->getTemplateFile($reflection, $format);
            $reflection = $reflection->getParentClass();
        } while (!file_exists($templateFile) && $reflection);

        if (!file_exists($templateFile)) {
            return null;
        }
        return file_get_contents($templateFile);
    }

    protected function getTemplateFile(\ReflectionClass $componentReflection, $format) {
        $componentName = FileRouter::stripControllerName($componentReflection->getShortName());
        return dirname($componentReflection->getFileName()) . '/' . lcfirst($componentName) . '.' . $format;
    }

}