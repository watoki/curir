<?php
namespace spec\watoki\curir;

use watoki\curir\protocol\UploadedFile;
use watoki\scrut\Specification;

/**
 * Uploaded files are put in the request arguments as instances of UploadedFile
 *
 * @property \spec\watoki\curir\fixtures\WebRequestBuilderFixture request <-
 */
class BuildRequestWithFileUploadsTest extends Specification {

    function testUploadSingleFile() {
        $this->givenIHaveUploadedUnder_AFile_OfType_WithSize_Into('foo', 'some_file.txt', 'text/plain', '32', '/tmp/bar');

        $this->request->whenIBuildTheRequest();
        $this->thenTheArgument_ShouldBeAnUploadedFile('foo');
        $this->thenTheNameOf_ShouldBe('foo', 'some_file.txt');
        $this->thenTheTypeOf_ShouldBe('foo', 'text/plain');
        $this->thenTheSizeOf_ShouldBe('foo', 32);
        $this->thenTheTemporaryNameOf_ShouldBe('foo', '/tmp/bar');
    }

    function testUploadMultipleFiles() {
        $this->givenIHaveUploadedUnder_TheFiles('files', array('one.txt', 'two.txt'));

        $this->request->whenIBuildTheRequest();
        $this->thenTheArgument_ShouldBe_UploadedFiles('files', 2);
        $this->thenUploadedFile_ShouldHaveTheName('files', 1, 'one.txt');
        $this->thenUploadedFile_ShouldHaveTheName('files', 2, 'two.txt');
    }

    ##########################################################################################

    private function givenIHaveUploadedUnder_AFile_OfType_WithSize_Into($field, $name, $type, $size, $tmpName) {
        $this->request->givenTheFile_Is($field, array(
            'name' => $name,
            'type' => $type,
            'size' => $size,
            'tmp_name' => $tmpName,
            'error' => 0
        ));
    }

    private function givenIHaveUploadedUnder_TheFiles($field, $names) {
        $files = array();
        foreach ($names as $name) {
            $files[] = array(
                'name' => $name,
                'type' => 'text/plain',
                'size' => 123,
                'tmp_name' => '/tmp/foo',
                'error' => 0
            );
        }
        $this->request->givenTheFile_Is($field, $files);
    }

    private function thenTheArgument_ShouldBeAnUploadedFile($argument) {
        $arguments = $this->request->request->getArguments();
        $this->assertTrue($arguments->has($argument), "No argument [$argument]");
        $this->assertTrue($arguments->get($argument) instanceof UploadedFile);
    }

    private function thenTheNameOf_ShouldBe($argument, $string1) {
        $this->assertEquals($string1, $this->getFile($argument)->getName());
    }

    private function thenTheTypeOf_ShouldBe($argument, $string1) {
        $this->assertEquals($string1, $this->getFile($argument)->getType());
    }

    private function thenTheSizeOf_ShouldBe($argument, $int) {
        $this->assertEquals($int, $this->getFile($argument)->getSize());
    }

    private function thenTheTemporaryNameOf_ShouldBe($argument, $string1) {
        $this->assertEquals($string1, $this->getFile($argument)->getTemporaryName());
    }

    private function thenTheArgument_ShouldBe_UploadedFiles($name, $count) {
        $this->assertCount($count, $this->request->request->getArguments()->get($name));
    }

    private function thenUploadedFile_ShouldHaveTheName($name, $pos, $string) {
        $files = $this->getFile($name);
        $this->assertEquals($string, $files[$pos - 1]->getName());

    }

    /**
     * @param $argument
     * @return UploadedFile|UploadedFile[]
     */
    private function getFile($argument) {
        return $this->request->request->getArguments()->get($argument);
    }

} 