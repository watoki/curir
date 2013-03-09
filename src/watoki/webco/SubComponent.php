<?php
namespace watoki\webco;

use watoki\collections\Map;
use watoki\factory\Factory;
use watoki\tempan\HtmlParser;

class SubComponent {

    /**
     * @var \watoki\webco\Module
     */
    private $root;

    /**
     * @var string
     */
    private $componentClass;

    /**
     * @var string The local name of the SubComponent for its super
     */
    private $name;

    function __construct($name, Module $root, $componentClass) {
        $this->name = $name;
        $this->root = $root;
        $this->componentClass = $componentClass;
    }

    public function render() {
        /** @var $component Component */
        $component = $this->root->findController($this->componentClass);
        $response = $component->respond(new Request(Request::METHOD_GET, '', new Map(), new Map()));
        return $this->postProcess($response->getBody());
    }

    private function postProcess($content) {
        $parser = new HtmlParser($content);

        $bodyElement = $parser->getRoot();
        if ($bodyElement->nodeName == 'html') {
            $bodyElement = $bodyElement->firstChild;
            while ($bodyElement->nodeName != 'body') {
                $bodyElement = $bodyElement->nextSibling;
                if (!$bodyElement) {
                    throw new \Exception('Cannot find body element while parsing sub component [' . $this->name . ']');
                }
            }
        }

        return substr($parser->toString($bodyElement), strlen('<body>'), -strlen('</body>'));
    }

}