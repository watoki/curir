<?php
namespace watoki\curir\composition;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\tempan\HtmlParser;
use watoki\curir\Path;
use watoki\curir\Request;
use watoki\curir\Response;
use watoki\curir\Url;
use watoki\curir\composition\SuperComponent;
use watoki\curir\controller\Component;

class PostProcessor {

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

    const HEADER_HTML_HEAD = 'x-html-head';

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

    public function postProcess(Response $response) {
        $body = $response->getBody();
        $parser = new HtmlParser($body);

        if (!$body || !$parser->isHtmlDocument()) {
            return $response;
        }

        $rendered = $parser->toString($this->extractBody($parser->getRoot()));
        $response->setBody(str_replace(array('<body>', '</body>'), '', $rendered));

        $elements = array();
        foreach ($this->headElements as $element) {
            $elements[] = $parser->toString($element);
        }
        $response->getHeaders()->set(self::HEADER_HTML_HEAD, implode("", $elements));

        return $response;
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
        if (!$url->getPath()->isAbsolute()) {
            $absolute = $route->copy();
            $absolute->getNodes()->append($value);
            $element->setAttribute($attributeName, $absolute->toString());
        }
    }

    private function replaceLinkUrl(\DOMElement $element, $attributeName) {
        if (!$element->hasAttribute($attributeName)) {
            return;
        }

        $attributeValue = $this->decode($element->getAttribute($attributeName));
        $url = Url::parse($attributeValue);

        $this->replaceUrlConsideringTarget($element, $attributeName, $url);
    }

    private function decode($string) {
        return urldecode(html_entity_decode($string));
    }

    private function replaceUrlConsideringTarget(\DOMElement $element, $attributeName, Url $url) {
        $target = $element->getAttribute('target');

        if ($target == '_top') {
            $element->removeAttribute('target');
        } else if (!$url->getHost() && $target != '_blank') {
            if ($target == '_self') {
                $element->removeAttribute('target');
            }
            $this->replaceWithDeepUrl($element, $attributeName, $url);
        }
    }

    private function replaceWithDeepUrl(\DOMElement $element, $attributeName, Url $url) {
        $state = $this->createState($element, $url);
        $subParams = $this->createSubParams($url, $state);
        $this->getSubState($state)->set($this->name, $subParams);

        $replace = new Url($this->super->getRoute(), $state, $url->getFragment());
        $element->setAttribute($attributeName, $replace->toString());
    }

    private function createSubParams(Url $url, Map $state) {
        $subParams = new Map();
        if ($this->isUrlEqualsRoute($url, $this->component)
                || $state->has(SuperComponent::PARAMETER_PRIMARY_REQUEST)
        ) {
            $subParams->set(SuperComponent::PARAMETER_TARGET, $url->getPath()->toString());
        }
        $subParams->merge($url->getParameters());
        return $subParams;
    }

    private function createState(\DOMElement $element, Url $url) {
        $state = new Map();
        if ($this->getActionName($url, $element) != 'get'
                || $url->getParameters()->has(SuperComponent::PARAMETER_PRIMARY_REQUEST)
        ) {
            $state->set(SuperComponent::PARAMETER_PRIMARY_REQUEST, $this->name);
        }
        $state->merge($this->parameters);
        return $state;
    }

    private function getSubState(Map $state) {
        if (!$state->has(SuperComponent::PARAMETER_SUB_REQUESTS)) {
            $state->set(SuperComponent::PARAMETER_SUB_REQUESTS, new Map());
        }
        return $state->get(SuperComponent::PARAMETER_SUB_REQUESTS);
    }

    private function isUrlEqualsRoute(Url $url, Component $component) {
        $urlPath = new Path($url->getPath()->getNodes()->slice(0, -1));
        $urlPath->getNodes()->append($url->getPath()->getLeafName());
        $urlRoute = strtolower($urlPath->toString());

        $subRoute = strtolower($component->getRoute()->toString());

        return $urlRoute != $subRoute;
    }

    private function replaceName(\DOMElement $child, $attributeName) {
        if (!$child->hasAttribute($attributeName)) {
            return;
        }

        $url = Url::parse('?' . urldecode(html_entity_decode($child->getAttribute($attributeName))));
        $replace = new Url(new Path());
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

    public function getHeadElements() {
        return $this->headElements;
    }
}