<?php
namespace watoki\webco\controller\sub;

use watoki\collections\Liste;
use watoki\tempan\HtmlParser;
use watoki\webco\Url;
use watoki\webco\controller\Module;

class HtmlSubComponent extends PlainSubComponent {

    static $assetElements = array(
        'img' => array('src'),
        'link' => array('href')
    );

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
        return $parser->toString($this->extractBody($parser->getRoot()));
    }

    /**
     * @param $root
     * @return mixed
     * @throws \Exception
     */
    private function extractBody(\DOMElement $root) {
        if ($root->nodeName != 'html') {
            throw new \Exception('Cannot render an HtmlSubComponent that does not return a valid HTML document.');
        }

        $head = $root->firstChild;
        if ($head->nodeName == 'head') {
            $this->replaceUrls($head);
            $this->collectHeadElements($head);
            $body = $head->nextSibling;
        } else {
            $body = $head;
        }

        while (!$body || $body->nodeName != 'body') {
            throw new \Exception('Cannot find body element while parsing sub component [' . $this->name . ']');
        }

        $this->replaceUrls($body);
        return $body;
    }

    /**
     * @param $head
     */
    private function collectHeadElements($head) {
        foreach ($head->childNodes as $headElement) {
            $this->headElements->append($headElement);
        }
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

    private function replaceUrls(\DOMElement $element) {
        $route = $this->getComponent()->getRoute();
        foreach ($element->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }

            /** @var $child \DOMElement */
            if (array_key_exists($child->nodeName, self::$assetElements)) {
                foreach ($child->attributes as $name => $attributeNode) {
                    if (in_array($name, self::$assetElements[$child->nodeName])) {
                        $value = $attributeNode->value;
                        $url = new Url($value);
                        if ($url->isRelative()) {
                            $child->setAttribute($name, $route . $value);
                        }
                    }
                }
            }

            $this->replaceUrls($child);
        }
    }

}