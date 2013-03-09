<?php
namespace watoki\webco\controller\sub;

use watoki\collections\Liste;
use watoki\tempan\HtmlParser;
use watoki\webco\controller\Module;

class HtmlSubComponent extends PlainSubComponent {

    /**
     * @var Liste|\DOMElement[]
     */
    private $headElements;

    function __construct($name, Module $root, $componentClass) {
        parent::__construct($name, $root, $componentClass);
        $this->headElements = new Liste();
    }

    public function render() {
        return $this->postProcess(parent::render());
    }

    private function postProcess($content) {
        $parser = new HtmlParser($content);

        $bodyElement = $this->findBodyElement($parser->getRoot());

        return $parser->toString($bodyElement);
    }

    /**
     * @param $root
     * @return mixed
     * @throws \Exception
     */
    private function findBodyElement(\DOMElement $root) {
        if ($root->nodeName != 'html') {
            throw new \Exception('Cannot render an HtmlSubComponent that does not return a valid HTML document.');
        }

        $head = $root->firstChild;
        if ($head->nodeName == 'head') {
            foreach ($head->childNodes as $headElement) {
                $this->headElements->append($headElement);
            }
            $body = $head->nextSibling;
        } else {
            $body = $head;
        }

        while (!$body || $body->nodeName != 'body') {
            throw new \Exception('Cannot find body element while parsing sub component [' . $this->name . ']');
        }

        return $body;
    }

    /**
     * @param string|null $nodeName Filter by node name (if given)
     * @return \watoki\collections\Liste
     */
    public function getHeadElements($nodeName = null) {
        if (!$nodeName) {
            return $this->headElements;
        } else {
            return $this->headElements->filter(function (\DOMNode $element) use ($nodeName) {
                return $element->nodeName == $nodeName;
            });
        }
    }

}