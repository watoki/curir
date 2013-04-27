<?php
namespace watoki\curir\composition;

use watoki\collections\Liste;
use watoki\collections\Map;
use watoki\dom\Element;
use watoki\dom\Parser;
use watoki\dom\Printer;
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
     * @var Liste|Element[]
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
        if (!$body) {
            return $response;
        }

        $parser = new Parser($body);

        $html = $parser->findElement('html');
        if ($html) {
            $this->postProcessHtml($response, $html);
        }

        return $response;
    }

    /**
     * @param Response $response
     * @param $html
     */
    private function postProcessHtml(Response $response, $html) {
        $printer = new Printer();
        $response->setBody($printer->printNodes($this->extractBody($html)->getChildren()));
    }

    /**
     * @param $root
     * @return Element
     * @throws \Exception
     */
    private function extractBody(Element $root) {
        $head = $root->findChildElement('head');
        if ($head) {
            $this->replaceUrls($head);
            $this->collectHeadElements($head);
        }

        $body = $root->findChildElement('body');
        if (!$body) {
            throw new \Exception('Cannot find body element while parsing sub component [' . $this->name . ']');
        }

        $this->replaceUrls($body);
        return $body;
    }

    private function collectHeadElements(Element $head) {
        foreach ($head->getChildElements() as $headElement) {
            $this->headElements->append($headElement);
        }
    }

    private function replaceUrls(Element $element) {
        foreach ($element->getChildren() as $child) {
            if (!$child instanceof Element) {
                continue;
            }

            if (array_key_exists($child->getName(), self::$assetElements)) {
                $this->replaceRelativeUrl($child, self::$assetElements[$child->getName()]);
            }

            if (array_key_exists($child->getName(), self::$linkElements)) {
                $this->replaceLinkUrl($child, self::$linkElements[$child->getName()]);
            }

            if (array_key_exists($child->getName(), self::$formElements)) {
                $this->replaceName($child, self::$formElements[$child->getName()]);
            }

            $this->replaceUrls($child);
        }
    }

    private function replaceRelativeUrl(Element $element, $attributeName) {
        if (!$element->getAttribute($attributeName)) {
            return;
        }

        $route = $this->component->getBaseRoute();
        $value = $element->getAttribute($attributeName)->getValue();
        $url = Url::parse($value);
        if (!$url->getPath()->isAbsolute()) {
            $absolute = $route->copy();
            $absolute->getNodes()->append($value);
            $element->setAttribute($attributeName, $absolute->toString());
        }
    }

    private function replaceLinkUrl(Element $element, $attributeName) {
        if (!$element->getAttribute($attributeName)) {
            return;
        }

        $attributeValue = $this->decode($element->getAttribute($attributeName)->getValue());
        $url = Url::parse($attributeValue);

        $this->replaceUrlConsideringTarget($element, $attributeName, $url);
    }

    private function decode($string) {
        return urldecode(html_entity_decode($string));
    }

    private function replaceUrlConsideringTarget(Element $element, $attributeName, Url $url) {
        $targetAttribute = $element->getAttribute('target');
        $target = $targetAttribute ? $targetAttribute->getValue() : null;

        if ($target == '_top') {
            $element->getAttributes()->removeElement($targetAttribute);
        } else if (!$url->getHost() && $target != '_blank') {
            if ($target == '_self') {
                $element->getAttributes()->removeElement($targetAttribute);
            }
            $this->replaceWithDeepUrl($element, $attributeName, $url);
        }
    }

    private function replaceWithDeepUrl(Element $element, $attributeName, Url $url) {
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

    private function createState(Element $element, Url $url) {
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

    private function replaceName(Element $child, $attributeName) {
        if (!$child->getAttribute($attributeName)) {
            return;
        }

        $url = Url::parse('?' . urldecode(html_entity_decode($child->getAttribute($attributeName)->getValue())));
        $replace = new Url(new Path());
        $replace->getParameters()->set(SuperComponent::PARAMETER_SUB_REQUESTS, new Map(array($this->name => $url->getParameters())));

        $replaceName = substr($replace->toString(), 1, -1);
        $child->setAttribute($attributeName, $replaceName);
    }

    private function getActionName(Url $url, Element $element) {
        if ($url->getParameters()->has('action')) {
            return $url->getParameters()->get('action');
        } else if ($element->getName() == 'form' && $element->getAttribute('method')) {
            return $element->getAttribute('method')->getValue();
        } else {
            return Request::METHOD_GET;
        }
    }

    public function getHeadElements() {
        return $this->headElements;
    }
}