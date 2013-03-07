<?php
namespace spec\watoki\webco;
 
class Step {

    /**
     * @var Test
     */
    public $test;

    function __construct(Test $test) {
        $this->test = $test;
    }

}
