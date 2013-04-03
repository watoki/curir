<?php
namespace spec\watoki\curir;

use spec\watoki\curir\steps\Given;
use spec\watoki\curir\steps\Then;
use spec\watoki\curir\steps\When;

/**
 * @property Given given
 * @property When when
 * @property Then then
 */
abstract class Test extends \PHPUnit_Framework_TestCase {

    public $undos = array();

    static $folder = __DIR__;

    protected function setUp() {
        parent::setUp();

        foreach (array('given', 'when', 'then') as $step) {
            $class = get_class($this) . '_' . ucfirst($step);
            if (!class_exists($class)) {
                $class = 'spec\watoki\curir\steps\\' . ucfirst($step);
            }
            $this->$step = new $class($this);
        }
    }

    protected function tearDown() {
        foreach (array_reverse($this->undos) as $undo) {
            $undo();
        }

        parent::tearDown();
    }

}
