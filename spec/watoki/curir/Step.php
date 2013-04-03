<?php
namespace spec\watoki\curir;
 
class Step {

    /**
     * @var Test
     */
    public $test;

    function __construct(Test $test) {
        $this->test = $test;
    }

}
