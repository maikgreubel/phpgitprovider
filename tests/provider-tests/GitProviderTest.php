<?php
namespace Nkey\GitProvider\Tests;

use Nkey\GitProvider\GitProvider;
use Generics\Util\Directory;
use Generics\Streams\FileOutputStream;
use Generics\Util\RandomString;

class GitProviderTest extends \PHPUnit_Framework_TestCase
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

    public function testSimple()
    {
        $this->provider->create(true, true);
        $this->provider->setAuthor("John Doe", "john@doe.tld");
        $this->provider->setProjectName('A test repository');
    }

    public function testAddFile()
    {
        $this->provider->create();
        $this->provider->setAuthor("John Doe", "john@doe.tld");
        $file = new FileOutputStream($this->provider->getPath() . "/README.md");
        $file->write("Some test readme\n");
        $file->write("==\n");
        $file->close();

        $this->provider->addToIndex("README.md");
        $this->assertTrue($this->provider->isStaged("README.md"));
    }

    public function testStaged()
    {
        $this->provider->create();
        $this->provider->setAuthor("John Doe", "john@doe.tld");
        $file = new FileOutputStream($this->provider->getPath() . "/stub");
        $file->write("Stub data\n");
        $file->close();

        $this->provider->addToIndex("stub");

        $this->assertTrue($this->provider->isStaged("stub"));
        $this->assertFalse($this->provider->isStaged("README.md"));
    }

    public function testCommit()
    {
        $this->provider->create();
        $this->provider->setAuthor("John Doe", "john@doe.tld");
        $file = new FileOutputStream($this->provider->getPath() . "/stub");
        $file->write("Stub data\n");
        $file->close();

        $this->provider->addToIndex("stub");

        $this->assertTrue($this->provider->isStaged("stub"));

        $this->provider->commit("some test commit");

        $this->assertFalse($this->provider->isStaged("stub"));
    }

    public function testRemove()
    {
        $this->provider->create();
        $this->provider->setAuthor("John Doe", "john@doe.tld");
        $file = new FileOutputStream($this->provider->getPath() . "/stub");
        $file->write("Stub data\n");
        $file->close();

        $this->provider->addToIndex("stub");

        $this->assertTrue($this->provider->isStaged("stub"));

        $this->provider->commit("some test commit");

        $this->assertFalse($this->provider->isStaged("stub"));

        $this->provider->remove("stub");
        $this->provider->commit("stub removed");

        $repo = new Directory($this->provider->getPath());
        $this->assertFalse($repo->fileExists("stub"));
    }

    public function testClone()
    {
        $this->provider->create(true, true);

        $random = RandomString::generate(8, RandomString::ASCII);

        $dir = new Directory(getcwd() . "/" . $random);
        $dir->create();

        $clone = new GitProvider($dir->getPath());
        $clone->cloneFrom($this->provider->getPath());
        $clone->setAuthor("John Doe", "john@doe.tld");

        $this->assertTrue($clone->isEmpty(".git"));

        $clone->destroy();
    }

    public function testPush()
    {
        $this->provider->create(true, true);

        $random = RandomString::generate(8, RandomString::ASCII);

        $dir = new Directory(getcwd() . "/" . $random);
        $dir->create();

        $clone = new GitProvider($dir->getPath());
        $clone->cloneFrom($this->provider->getPath());
        $clone->setAuthor("John Doe", "john@doe.tld");

        $file = new FileOutputStream($clone->getPath() . "/stub");
        $file->write("Stub data\n");
        $file->close();

        $clone->addToIndex("stub");
        $clone->commit("Initial commit");

        $clone->push();

        $clone->destroy();
    }

    public function testPull()
    {
        $this->provider->create(true, true);

        $random = RandomString::generate(8, RandomString::ASCII);

        $clone1dir = new Directory(getcwd() . "/" . $random);
        $clone1dir->create();

        $random = RandomString::generate(8, RandomString::ASCII);

        $clone2dir = new Directory(getcwd() . "/" . $random);
        $clone2dir->create();

        $clone1 = new GitProvider($clone1dir->getPath());
        $clone1->cloneFrom($this->provider->getPath());
        $clone1->setAuthor("John Doe", "john@doe.tld");

        $clone2 = new GitProvider($clone2dir->getPath());
        $clone2->cloneFrom($this->provider->getPath());
        $clone2->setAuthor("Jane Doe", "jane@doe.tld");

        $file = new FileOutputStream($clone1->getPath() . "/stub");
        $file->write("Stub data\n");
        $file->close();

        $clone1->addToIndex("stub");
        $clone1->commit("Initial commit");
        $clone1->push();

        $this->assertFalse($clone2dir->fileExists("stub"));

        $clone2->pull();

        $this->assertTrue($clone2dir->fileExists("stub"));

        $clone1->destroy();
        $clone2->destroy();
    }

    public function testCheckout()
    {
        $this->provider->create(true, true);

        $random = RandomString::generate(8, RandomString::ASCII);

        $clone1dir = new Directory(getcwd() . "/" . $random);
        $clone1dir->create();

        $random = RandomString::generate(8, RandomString::ASCII);

        $clone2dir = new Directory(getcwd() . "/" . $random);
        $clone2dir->create();

        $clone1 = new GitProvider($clone1dir->getPath());
        $clone1->cloneFrom($this->provider->getPath());
        $clone1->setAuthor("John Doe", "john@doe.tld");

        $clone2 = new GitProvider($clone2dir->getPath());
        $clone2->cloneFrom($this->provider->getPath());
        $clone1->setAuthor("Jane Doe", "jane@doe.tld");

        $file = new FileOutputStream($clone1->getPath() . "/stub");
        $file->write("Stub data\n");
        $file->close();

        $clone1->addToIndex("stub");
        $clone1->commit("Initial commit");
        $clone1->push();

        $clone2->pull();
        $this->assertTrue($clone2dir->fileExists("stub"));

        /**
         * branch & checkout tests
         */
        $clone1->createBranch("v1.0");
        $clone1->checkout("v1.0");

        $file = new FileOutputStream($clone1->getPath() . "/stub2");
        $file->write("Stub data2\n");
        $file->close();

        $clone1->addToIndex("stub2");
        $clone1->commit("stub2 added");

        $clone1->push("v1.0");

        $clone2->pull();
        $clone2->checkout("v1.0");

        $this->assertTrue($clone2dir->fileExists("stub2"));

        $clone1->destroy();
        $clone2->destroy();
    }
}
