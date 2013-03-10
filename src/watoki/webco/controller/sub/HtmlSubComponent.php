<?php
namespace watoki\webco\controller\sub;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\tempan\HtmlParser;
use watoki\webco\Url;
use watoki\webco\controller\Component;
use watoki\webco\controller\Module;

class HtmlSubComponent extends PlainSubComponent {

    static $assetElements = array(
        'img' => array('src'),
        'link' => array('href')
    );

    static $linkElements = array(
        'form' => array('action'),
        'a' => array('href')
    );

    /**
     * @var Liste|\DOMElement[]
     */
    private $headElements;

    function __construct(Component $super, $componentClass) {
        parent::__construct($super, $componentClass);
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
            throw new \Exception('Cannot find body element while parsing sub component [' . $this->getName() . ']');
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
        foreach ($element->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }

            if (array_key_exists($child->nodeName, self::$assetElements)) {
                $this->replaceRelativeUrl($child, self::$assetElements);
            } else if (array_key_exists($child->nodeName, self::$linkElements)) {
                $this->replaceRelativeUrl($child, self::$linkElements);
                $this->replaceLinkUrl($child, self::$linkElements);
            }

            $this->replaceUrls($child);
        }
    }

    private function replaceRelativeUrl(\DOMElement $element, $elements) {
        $route = $this->getComponent()->getBaseRoute();
        foreach ($element->attributes as $name => $attributeNode) {
            if (in_array($name, $elements[$element->nodeName])) {
                $value = $attributeNode->value;
                $url = Url::parse($value);
                if ($url->isRelative()) {
                    $element->setAttribute($name, $route . $value);
                }
            }
        }
    }

    private function replaceLinkUrl(\DOMElement $element, $elements) {
        $subName = $this->getName();
        $route = $this->super->getRoute();
        foreach ($element->attributes as $name => $attributeNode) {
            if (in_array($name, $elements[$element->nodeName])) {
                $url = Url::parse($attributeNode->value);
                if ($url->isSameHost()) {
                    $replace = new Url($route);
                    $replace->setFragment($url->getFragment());

                    $subParams = new Map(array('.' => $url->getResource()));
                    foreach ($url->getParameters() as $key => $value) {
                        $subParams->set($key, $value);
                    }
                    $replace->getParameters()->set('.', new Map(array($subName => $subParams)));

                    $element->setAttribute($name, $replace->toString());
                }
            }
        }
    }

}