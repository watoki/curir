# curir [![Build Status](https://travis-ci.org/watoki/curir.png?branch=master)](https://travis-ci.org/watoki/curir)

*curir* is a zero-configuration web delivery system for PHP applications that is optimized for rapid prototyping.

## Installation ##

To use curir in your own project with [Composer], add the following lines to your `composer.json`.

    "require" : {
        "watoki/curir" : "*"
    }

To install curir as a stand-alone project you can use

    git clone https://github.com/watoki/curir.git
    cd curir
    php install.php

[Composer]: http://getcomposer.org/

## Quickstart ##

The easiest way to use *curir* is to have this one line in a `index.php` file

    WebApplication::quickStart('MyResource');

which packs a request into a `Request` object, passes it to the given `Resource` and flushes
its `Response`. The class `MyResource` could look like

    class MyResource extends Resource {
        public function respond(Request $request) {
            return new Response('Hello World');
        }
    }

or it could extend `DynamicResource` which invokes a method corresponding with the *METHOD* of the HTTP request which
can be overwritten with a `method` query parameter.

    class MyResource extends DynamicResource {
        public function doGet() {
            return "Hello World";
        }
    }

## Routing ##

There isn't actually much routing going on. All requests are passed to the *root* `Resource` provided to `WebApplication`.
Most web applications expose more than one resource though, so the root resource may pass the request to further
resources. The `Container` class for example passes the request to a child based on the requested path. A *child* resource
is simply a class in the folder belonging to a `Container`. If the container is called `RootResource`, its folder is called `root`.

Some routes are dynamic, e.g. `posts/42` where `42` might be the post ID. For this purpose, placeholders resources can
be used which are resources starting with `xx`. Also, static resources don't need a corresponding class.

So the file structure of a blog application could look like

    web
    |- RootResource.php
    |- root
      |- PostsResource.php
      |- SearchResource.php
      |- posts
        |- xxPostResource.php
      |- about.html

The target path is passed as a query parameter, by default `-` (e.g.`http://my.site/blog/index.php?-=posts/42`). On Apache, you
can use `mod_rewrite` to send all requests to `index.php` with

    RewriteEngine On
    RewriteBase /blog
    RewriteRule ^(.*)$ index.php?-=$1 [L,QSA]

## Parameters ##

Query parameters are translated into method arguments by the `DynamicResource`. So the request
`my.site/blog/search?text=story&after=2001-01-01` translates to

    class SearchResource extends DynamicResource {
        public function doGet(DateTime $after, $text = null) {
            // ...
        }
    }

Method arguments can be optional and also provide hints to which type a query parameter should be converted to.

## Rendering ##

A resource method (e.g. `doGet`) can return a `Responder` object which will be used by the resource to create a `Response`.
For example a redirecting response is created by returning a `Redirecter` instance. The response can also be rendered depending
on the requested format by returning a `Presenter` instance.