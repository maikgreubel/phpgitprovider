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
     * @expectedExceptionMessage Invalid commit message (must not be empty)
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

    /**
     * @expectedException Nkey\GitProvider\GitProviderException
     * @expectedExceptionMessage Branch master already exists
     */
    public function testCreateExistingBranch()
    {
        $this->provider->create();
        $this->provider->setAuthor("John Doe", "john@doe.tld");
        $file = new FileOutputStream($this->provider->getPath() . "/stub");
        $file->write("Stub data\n");
        $file->close();

        $this->provider->addToIndex("stub");
        $this->provider->commit("stub message");

        $this->provider->createBranch("master");
    }

    /**
     * @expectedException Nkey\GitProvider\GitProviderException
     * @expectedExceptionMessage Nothing commited yet on empty repository!
     */
    public function testPushInvalid()
    {
        $this->provider->create();
        $this->provider->setAuthor("John Doe", "john@doe.tld");
        $file = new FileOutputStream($this->provider->getPath() . "/stub");
        $file->write("Stub data\n");
        $file->close();

        $this->provider->addToIndex("stub");

        $this->provider->push();
    }

    /**
     * @expectedException Nkey\GitProvider\GitProviderException
     * @expectedExceptionMessage Invalid uri ftp://localhost/test.git given
     */
    public function testInvalidRemoteUri()
    {
        $this->provider->cloneFrom("ftp://localhost/test.git");
    }
}
