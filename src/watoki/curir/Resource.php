<?php
namespace watoki\curir;

use watoki\curir\http\Request;
use watoki\curir\http\Response;
use watoki\curir\http\Url;
use watoki\curir\resource\Container;

/**
 * A Resource responds to a Request with a Response.
 */
abstract class Resource {

    /** @var string */
    private $url;

    /** @var Container|null */
    private $parent;

    public function __construct(Url $url, Resource $parent = null) {
        $this->url = $url;
        $this->parent = $parent;
    }

    /**
     * @param Request $request
     * @return Response
     */
    abstract public function respond(Request $request);

    /**
     * @param null|string|Url $merge Will be merged with the Resource's URL
     * @return Url
     */
    public function getUrl($merge = null) {
        $url = $this->url->copy();
        if ($merge) {
            $url->merge($merge instanceof Url ? $merge : Url::parse($merge));
        }
        return $url;
    }

    /**
     * @return null|\watoki\curir\Resource
     */
    public function getParent() {
        return $this->parent;
    }

    /**
     * @param string $class Name of the ancestor class
     * @throws \InvalidArgumentException If ancestor does not exist
     * @return \watoki\curir\Resource
     */
    public function getAncestor($class) {
        $ancestor = $this;
        while ($ancestor) {
            if (get_class($ancestor) == $class) {
                return $ancestor;
            }
            $ancestor = $ancestor->getParent();
        }
        $me = get_class($this);
        throw new \InvalidArgumentException("[$me] does not have the ancestor [$class].");
    }

}
 