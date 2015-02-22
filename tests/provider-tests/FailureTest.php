<?php
namespace Nkey\GitProvider\Tests;

use Nkey\GitProvider\GitProvider;
use Generics\Util\Directory;
use Generics\Streams\FileOutputStream;

class FailureTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Git provider instance
     *
     * @var GitProvider
     */
    protected $provider;

    protected function setUp()
    {
        $path = getcwd() . "/test.git";
        if (file_exists($path)) {
            echo "Removing $path first\n";
            $d = new Directory($path);
            $d->remove(true);
        }
        $this->provider = new GitProvider($path);
    }

    protected function tearDown()
    {
        $path = getcwd() . "/test.git";
        if (file_exists($path)) {
            $d = new Directory($path);
            $d->remove(true);
        }
    }

    /**
     * @expectedException Nkey\GitProvider\GitProviderException
     */
    public function testCommitFail()
    {
        $this->provider->create();
        $this->provider->setAuthor("John Doe", "john@doe.tld");
        $file = new FileOutputStream($this->provider->getPath() . "/stub");
        $file->write("Stub data\n");
        $file->close();

        $this->provider->addToIndex("stub");
        $this->provider->commit("");
    }
}
