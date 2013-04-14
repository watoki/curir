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

    /**
     * @param string $format
     * @param string $rendererClass
     */
    public function setRenderer($format, $rendererClass) {
        $this->renderers[$format] = $rendererClass;
    }

    protected function setDefaultRenderers() {
        $this->setRenderer('json', JsonRenderer::$CLASS);
        $this->setRenderer('none', NoneRenderer::$CLASS);
    }

    /**
     * @param Component $component
     * @param $format
     * @return null|string
     */
    protected function getTemplate(Component $component, $format) {
        $templateFile = $this->getTemplateFile($component, $format);
        if (!file_exists($templateFile)) {
            return null;
        }
        return file_get_contents($templateFile);
    }

    protected function getTemplateFile(Component $component, $format) {
        $classReflection = new \ReflectionClass($component);
        $componentName = FileRouter::stripControllerName($classReflection->getShortName());
        return dirname($classReflection->getFileName()) . '/' . lcfirst($componentName) . '.' . $format;
    }

}