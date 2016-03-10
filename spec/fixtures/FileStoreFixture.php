<?php
namespace spec\watoki\curir\fixtures;

use watoki\factory\providers\CallbackProvider;
use watoki\scrut\Fixture;
use watoki\stores\Store;
use watoki\stores\stores\FlatFileStore;
use watoki\stores\stores\MemoryStore;

/**
 * @property MemoryStore store <-
 */
class FileStoreFixture extends Fixture {

    public function setUp() {
        parent::setUp();

        $this->spec->factory->setProvider(FlatFileStore::class, new CallbackProvider(function ($class, $args) {
            return new __FileStoreFixture_FlatFileStore($this->store, $args['basePath']);
        }));
    }

    public function givenAFile_WithContent($fileName, $content) {
        $this->store->write($content, $fileName);
    }
}

class __FileStoreFixture_FlatFileStore implements Store {

    private $store;
    private $basePath;

    public function __construct(Store $store, $basePath) {
        $this->store = $store;
        $this->basePath = rtrim($basePath, '/') . '/';
    }

    public function write($data, $key = null) {
        $this->store->write($data, $this->basePath . $key);
    }

    public function read($key) {
        return $this->store->read($this->basePath . $key);
    }

    public function remove($key) {
        $this->store->remove($this->basePath . $key);
    }

    public function has($key) {
        return $this->store->has($this->basePath . $key);
    }

    public function keys() {
        return array_map(function ($key) {
            return substr($key, strlen($this->basePath));
        }, $this->store->keys());
    }
}