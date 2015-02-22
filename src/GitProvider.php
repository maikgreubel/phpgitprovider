<?php
namespace Nkey\GitProvider;

use Generics\Util\Directory;
use Generics\Logger\LoggerTrait;

/**
 * Git provider
 *
 * @author Maik Greubel <greubel@nkey.de>
 */
class GitProvider
{
    use LoggerTrait;

    /**
     * The path to repository
     *
     * @var string
     */
    private $path;

    /**
     * The name (description) of the repository
     *
     * @var string
     */
    private $projectName;

    /**
     * Whether the repository is bare (non-workspace)
     *
     * @var bool
     */
    private $bare;

    /**
     * Create a new GitProvider object
     *
     * @param string $path
     * @param string $projectName
     * @param bool $bare
     * @throws GitProviderException
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * Check repository
     *
     * @throws GitProviderException
     */
    private function check()
    {
        if (! file_exists($this->path) || ! is_dir($this->path)) {
            throw new GitProviderException("Invalid repository path, {path} does not exist", array(
                'path' => $this->path
            ));
        }

        if ((! file_exists("$this->path/.git") || ! is_dir("$this->path/.git"))) {
            if (! file_exists("$this->path/HEAD")) {
                throw new GitProviderException("Invalid repository path, {path} does not seems to be a git repo", array(
                    'path' => $this->path
                ));
            }
            $this->bare = true;
        } else {
            $this->bare = false;
        }
    }

    /**
     * Intialize a brand new repository
     *
     * @param string $path
     *            The path where it will be created
     * @param bool $bare
     *            Whether it will be a bare repository
     * @param bool $shared
     *            Whether it should be a shared (or private = false) repository
     *
     * @throws GitProviderException
     */
    private function init($path, $bare = false, $shared = false)
    {
        $parameters = array();

        if ($bare) {
            $parameters[] = '--bare';
        }

        if ($shared) {
            $parameters[] = '--shared=0775';
        } else {
            $parameters[] = '--shared=0700';
        }

        $parameters[] = sprintf("'%s'", $path);

        $this->execute("init", $parameters);

        $dir = new Directory($this->path);
        $this->path = $dir->getPath();
    }

    /**
     * Generates the execution command by parsing the context
     *
     * @param string $command
     * @param array $context
     * @return string
     */
    private function generateExecutionCommand($command, $context)
    {
        $toExecute = "git $command";
        foreach ($context as $argument => $parameter) {
            if (! is_int($argument)) {
                $toExecute .= sprintf(" %s %s", $argument, $parameter);
            } else {
                $toExecute .= " $parameter";
            }
        }

        $toExecute .= "  2>&1";

        return $toExecute;
    }

    /**
     * Parse the output of command
     *
     * @param string $command
     *            The command which has been executed
     * @param string $toExecute
     *            The full command line for logging purposes
     * @param array $output
     *            The output which the command has been done
     * @param int $returnvalue
     *            The return value of the command
     *
     * @throws GitProviderException
     *
     * @return string|array The parsed command
     */
    private function evaluateOutput($command, $toExecute, $output, $returnvalue, $outputAsString)
    {
        $this->getLog()->debug("{executed}\ncode: {result}\noutput:{output}", array(
            'excuted' => $toExecute,
            'result' => $returnvalue,
            'output' => implode("\n", $output)
        ));

        if ($returnvalue != 0) {
            throw new GitProviderException("Could not execute command {command}: errorcode = {code}; {reason}", array(
                'command' => $command,
                'code' => $returnvalue,
                'reason' => implode('', $output)
            ));
        }

        $tmpout = array();
        foreach ($output as $line) {
            $line = trim($line);

            if (strlen($line) > 0) {
                $tmpout[] = $line;
            }
        }

        if (count($tmpout) == 0) {
            return $outputAsString ? "" : array();
        }

        return $outputAsString ? implode("\n", $tmpout) : $tmpout;
    }

    /**
     * Executes an abritary git command
     *
     * @param string $command
     *            The command to execute
     * @param array $context
     *            Contextual parameters
     * @return string|array The output of command
     *
     * @throws GitProviderException
     */
    private function execute($command, $context, $outputAsString = true)
    {
        $currentDir = null;
        if ($command != 'init') {
            $currentDir = getcwd();
            chdir($this->path);
        }

        $output = array();
        $returnvalue = 0;

        $toExecute = $this->generateExecutionCommand($command, $context);

        exec("$toExecute", $output, $returnvalue);

        if ($currentDir != null) {
            chdir($currentDir);
        }

        return $this->evaluateOutput($command, $toExecute, $output, $returnvalue, $outputAsString);
    }

    /**
     * Create a new repository
     *
     * @param string $path
     *            The path to repository
     * @param string $projectName
     *            The name of the project (will be written to description in case of bare)
     * @param bool $bare
     *            Whether the repository should be a bare (non-workspace) one
     * @param bool $shared
     *            Whether other users than the owner of the project have access to this
     */
    public function create($bare = false, $shared = false)
    {
        $this->init($this->path, $bare, $shared);
        $this->setProjectName($this->projectName);
        $this->bare = $bare;
    }

    /**
     * Set the project name; In case of bare repository it will be written to description file.
     *
     * @param string $projectName
     *
     * @throws GitProviderException
     */
    public function setProjectName($projectName)
    {
        $this->check();

        $this->projectName = $projectName;

        if ($projectName === null) {
            return;
        }

        $fd = null;

        if ($this->bare) {
            $fd = fopen("$this->path/description", "w");
            if (! $fd) {
                throw new GitProviderException("Setting the name not possible, could not open description file!");
            }
        }
        if ($fd) {
            if (($len = fputs($fd, $projectName, strlen($projectName))) === false || $len != strlen($projectName)) {
                throw new GitProviderException("Could not write description file!");
            }
            fflush($fd);
            fclose($fd);
        }
    }

    /**
     * Set the user and email address for the repository
     *
     * @param string $authorName
     *            The name of author (you)
     * @param string $authorEmail
     *            The email address of author (your email)
     */
    public function setAuthor($authorName, $authorEmail)
    {
        $parameters = array();
        $parameters[] = '--local';
        $parameters["user.name"] = sprintf("'%s'", $authorName);
        $this->execute("config", $parameters);

        $parameters = array();
        $parameters[] = '--local';
        $parameters["user.email"] = sprintf("'%s'", $authorEmail);
        $this->execute("config", $parameters);
    }

    /**
     * Retrieve the absolute path to repository
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Add a subset of entries to index
     *
     * @param string $pattern
     * @throws GitProviderException
     */
    public function addToIndex($pattern = ".")
    {
        $parameters = array();
        if (! strlen($pattern) > 0) {
            throw new GitProviderException("Invalid pattern");
        }
        $parameters[] = $pattern;

        $this->execute("add", $parameters);
    }

    /**
     * Check whether a subset of files is staged
     *
     * @param string $pattern
     * @return boolean
     */
    public function isStaged($pattern)
    {
        $parameters = array();
        $parameters[] = "--cached";
        $parameters[] = "--name-only";

        $files = $this->execute("diff", $parameters, false);
        foreach ($files as $file) {
            if (preg_match("/$pattern/", $file)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Commits the staged area
     *
     * @param string $message
     *
     * @throws GitProviderException
     */
    public function commit($message)
    {
        if (strlen($message) == 0) {
            throw new GitProviderException("Invalid commit message (must not be empty)");
        }

        $parameters = array();
        $parameters["-m"] = sprintf("'%s'", $message);

        $this->execute("commit", $parameters);
    }

    /**
     * Remove a file from repository (or staging area)
     *
     * @param string $pattern
     *            The file to be removed
     * @param boolean $force
     *            Whether to force the removal
     */
    public function remove($pattern, $force = false)
    {
        $parameters = array();

        if ($this->isStaged($pattern)) {
            $parameters[] = '--cached';
        }

        if ($force) {
            $parameters[] = '-f';
        }

        $parameters[] = $pattern;

        $this->execute('rm', $parameters);
    }

    /**
     * Clones an existing repository
     *
     * @param string $uri
     */
    public function cloneFrom($uri)
    {
        $parameters = array();

        $uri = str_replace('\\', '/', $uri);

        if (substr($uri, 0, 4) == 'http' || substr($uri, 0, 3) == 'git' || substr($uri, 0, 3) == 'ssh') {
            $parameters[] = $uri;
        } else {
            $dir = new Directory($uri);
            if ($dir->exists()) {
                $uri = sprintf("file://%s%s", (substr($uri, 0, 1) == '/' ? '' : '/'), $uri);
                $parameters[] = sprintf("'%s'", $uri);
            }
        }

        if (count($parameters) === 0) {
            throw new GitProviderException("Invalid uri {uri} given", array(
                'uri' => $uri
            ));
        }

        $dir = new Directory($this->path);
        if (! $dir->exists()) {
            $dir->create(true);
        }
        $this->path = $dir->getPath();
        $parameters[] = ".";

        $this->execute("clone", $parameters);
    }

    /**
     * Checks whether working repository is empty
     *
     * @param string $filter
     *            The filter to skip from check
     *
     * @throws GitProviderException
     *
     * @return boolean
     */
    public function isEmpty($filter = null)
    {
        if ($this->bare) {
            throw new GitProviderException("Could not check emptyness of bare repository");
        }

        $dir = new Directory($this->path);
        return $dir->isEmpty($filter);
    }

    /**
     * Destroy the repository
     */
    public function destroy()
    {
        $dir = new Directory($this->path);
        $dir->remove(true);
    }

    /**
     * Check whether a branch exists
     *
     * @param string $branchName
     * @return boolean
     */
    private function hasBranch($branchName)
    {
        $branches = $this->execute("branch", array(), false);
        if (count($branches) == 0) {
            return false;
        }
        foreach ($branches as $branch) {
            if ($branch[0] === '*') {
                $branch[0] = ' ';
            }
            $branch = trim($branch);
            if ($branch == $branchName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Push the commited changes to origin repository
     *
     * @param string $branch
     *            The branch to push
     * @param string $remote
     *            The remote identifier
     */
    public function push($branch = "master", $remote = "origin")
    {
        if (! $this->hasBranch("master")) {
            throw new GitProviderException("Nothing commited yet on empty repository!");
        }

        $parameters = array();
        $parameters[] = $remote;
        if ($this->hasBranch($branch)) {
            $parameters[] = $branch;
        }

        $this->execute("push", $parameters);
    }

    /**
     * Pull all changes from origin repository
     *
     * @param string $remote
     *            The remote identifier
     */
    public function pull($remote = "origin")
    {
        $parameters = array();
        $parameters[] = $remote;

        $this->execute("pull", $parameters);
    }

    /**
     * Create a new branch
     *
     * @param string $branch
     *            The name of the new created branch
     *
     * @throws GitProviderException
     */
    public function createBranch($branch)
    {
        if ($this->hasBranch($branch)) {
            throw new GitProviderException("Branch {branch} already exists", array(
                'branch' => $branch
            ));
        }

        $parameters = array();
        $parameters[] = $branch;

        $this->execute("branch", $parameters);
    }

    /**
     * Checkout a branch
     *
     * @param string $branch
     *            The name of branch to checkout (empty branch name will checkout current branch)
     *
     * @throws GitProviderException
     */
    public function checkout($branch = "")
    {
        $parameters = array();

        if (strlen($branch) > 0) {
            $parameters[] = $branch;
        }

        $this->execute("checkout", $parameters);
    }
}
