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

    /**
     * @expectedException Nkey\GitProvider\GitProviderException
     * @expectedExceptionMessage Invalid pattern
     */
    public function testAddEmpty()
    {
        $this->provider->create();
        $this->provider->setAuthor("John Doe", "john@doe.tld");
        $file = new FileOutputStream($this->provider->getPath() . "/stub");
        $file->write("Stub data\n");
        $file->close();

        $this->provider->addToIndex("");

        $this->provider->push();
    }

    /**
     * @expectedException Nkey\GitProvider\GitProviderException
     * @expectedExceptionMessage Could not write description file on non-bare repository!
     */
    public function testSetDescriptionOnWorkspace()
    {
        $this->provider->create();
        $this->provider->setAuthor("John Doe", "john@doe.tld");
        $this->provider->setProjectName("This will went wrong!");
    }

    /**
     * @expectedException Nkey\GitProvider\GitProviderException
     * @expectedExceptionMessage Could not check emptyness of bare repository
     */
    public function testNonEmptyBare()
    {
        $this->provider->create(true, true);
        $this->assertFalse($this->provider->isEmpty());
    }

    /**
     * @expectedException Nkey\GitProvider\GitProviderException
     * @expectedExceptionRegExp Invalid repository path, \w+ does not exist
     */
    public function testNonExistingRepositoryAction()
    {
        $this->provider->setProjectName("This will fail");
    }

    /**
     * @expectedException Nkey\GitProvider\GitProviderException
     * @expectedExceptionRegExp Invalid repository path, \w+ does not seems to be a git repo
     */
    public function testInvalidRepositoryAction()
    {
        $this->provider->getDirectory()->create(true);
        $this->provider->setProjectName("This will fail");
    }
}
