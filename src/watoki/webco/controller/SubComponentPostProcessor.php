<?php
namespace watoki\webco\controller;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\tempan\HtmlParser;
use watoki\webco\Request;
use watoki\webco\Url;

class SubComponentPostProcessor {

    public static $CLASS = __CLASS__;

    static $assetElements = array(
        'img' => 'src',
        'link' => 'href',
        'input' => 'src',
        'form' => 'action',
        'a' => 'href'
    );

    static $linkElements = array(
        'form' => 'action',
        'a' => 'href'
    );

    static $formElements = array(
        'input' => 'name',
        'textarea' => 'name',
        'select' => 'name',
        'button' => 'name'
    );

    /**
     * @var Map
     */
    public $parameters;

    /**
     * @var Component
     */
    public $component;

    /**
     * @var Component
     */
    public $super;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Liste|\DOMElement[]
     */
    private $headElements;

    public function __construct($name, Map $parameters, Component $component, Component $super) {
        $this->name = $name;
        $this->parameters = $parameters;
        $this->component = $component;
        $this->super = $super;
        $this->headElements = new Liste();
    }

    public function postProcess($content) {
        if (!$content || substr(trim($content), 0, 5) != '<html') {
            return $content;
        }

        $parser = new HtmlParser($content);
        $rendered = $parser->toString($this->extractBody($parser->getRoot()));
        return str_replace(array('<body>', '</body>'), '', $rendered);
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
        foreach ($element->childNodes as $child) {
            if (!$child instanceof \DOMElement) {
                continue;
            }

            if (array_key_exists($child->nodeName, self::$assetElements)) {
                $this->replaceRelativeUrl($child, self::$assetElements[$child->nodeName]);
            }

            if (array_key_exists($child->nodeName, self::$linkElements)) {
                $this->replaceLinkUrl($child, self::$linkElements[$child->nodeName]);
            }

            if (array_key_exists($child->nodeName, self::$formElements)) {
                $this->replaceName($child, self::$formElements[$child->nodeName]);
            }

            $this->replaceUrls($child);
        }
    }

    private function replaceRelativeUrl(\DOMElement $element, $attributeName) {
        if (!$element->hasAttribute($attributeName)) {
            return;
        }

        $route = $this->component->getBaseRoute();
        $value = $element->getAttribute($attributeName);
        $url = Url::parse($value);
        if ($url->isRelative()) {
            $element->setAttribute($attributeName, $route . $value);
        }
    }

    // TODO This whole strtolower logic should be somewhere central
    private function replaceLinkUrl(\DOMElement $element, $attributeName) {
        if (!$element->hasAttribute($attributeName)) {
            return;
        }

        $subRoute = strtolower($this->component->getRoute());
        $route = $this->super->getRoute();
        $url = Url::parse($element->getAttribute($attributeName));

        if ($url->isSameHost()) {

            $subParams = new Map();
            if (strtolower($url->getResourceDir() . $url->getResourceBaseName()) != $subRoute) {
                $subParams->set(SuperComponent::PARAMETER_TARGET, $url->getResource());
            }
            $subParams->merge($url->getParameters());

            $state = $this->parameters->copy();
            if ($this->getActionName($url, $element) != 'get') {
                $state->set(SuperComponent::PARAMETER_PRIMARY_REQUEST, $this->name);
            }

            if (!$state->has(SuperComponent::PARAMETER_SUB_REQUESTS)) {
                $state->set(SuperComponent::PARAMETER_SUB_REQUESTS, new Map());
            }

            $subState = $state->get(SuperComponent::PARAMETER_SUB_REQUESTS);
            $subState->set($this->name, $subParams);

            $replace = new Url($route, $state, $url->getFragment());
            $element->setAttribute($attributeName, $replace->toString());
        }
    }

    private function replaceName(\DOMElement $child, $attributeName) {
        if (!$child->hasAttribute($attributeName)) {
            return;
        }

        $url = Url::parse('?' . $child->getAttribute($attributeName));
        $replace = new Url('');
        $replace->getParameters()->set('.', new Map(array($this->name => $url->getParameters())));

        $replaceName = substr($replace->toString(), 1, -1);
        $child->setAttribute($attributeName, $replaceName);
    }

    private function getActionName(Url $url, \DOMElement $element) {
        if ($url->getParameters()->has('action')) {
            return $url->getParameters()->get('action');
        } else if ($element->nodeName == 'form' && $element->hasAttribute('method')) {
            return $element->getAttribute('method');
        } else {
            return Request::METHOD_GET;
        }
    }
}