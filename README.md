# curir [![Build Status](https://travis-ci.org/watoki/curir.png?branch=master)](https://travis-ci.org/watoki/curir)

*curir* is a web delivery system of PHP applications. Its services include **accepting** a request, **routing** it to its target, **composing** the response, and **sending** it back to the client.

Its design is focused on modularization, making sure that every module can be completely self-contained and independent of its surrounding to facilitate reusability.

## Usage ##

In short: A *module* is a folder that contains class extending `Module` with the same name as the folder. It's responsible to route a request to its target, which can be a static or dynamic resource. The latter can be implemented by a `Component` which in turn can be composed from several `SubComponents`.

(TODO: Describe Modules, Components and SubComponents in a more structured way)

### Hello World ###

This might have been a little too short. So for a minimal example, let's build a "Hello World" application.

(TODO: Tutorial for a simple application)

## Installation ##

There are three options. If you already have [Composer], you can use

	php composer.phar create-project watoki/curir

to check out *curir* as a stand-alone project (you'll need git and php as well). To run the test suite use

	phpunit curir

If you don't have Composer yet, or want to install a different branch you can use

    git clone https://github.com/watoki/curir.git
    php curir/install.php

To use it in your own project, add the following lines to your `composer.json`.

    "require" : {
        "watoki/curir" : "*"
    },
    "minimum-stability": "dev"

[Composer]: http://getcomposer.org/