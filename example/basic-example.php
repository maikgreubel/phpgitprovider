<?php
use Nkey\GitProvider\GitProvider;

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . "/../src" . PATH_SEPARATOR . __DIR__ . "/../vendor");

require 'autoload.php';

try {
    // Create a provider instance
    $bare = new GitProvider("./test.git");

    // Create a bare repository
    $bare->create(true, true);

    // Set the project name
    $bare->setProjectName("This is only a test repository");

    // Now we can clone it...

    // First create a provider for the workspace repository
    $workspace = new GitProvider("./test-workspace");
    // Clone the bare repository
    $workspace->cloneFrom($bare->getPath());
    // First set author to be able to commit
    $workspace->setAuthor("John Doe", "john@doe.tld");

    // Add some crucial data to workspace
    $fd = fopen($workspace->getPath() . "/I-am-some-stupid-file.txt", "w");
    if ($fd) {
        fputs($fd, "Hello repository", 16);
        fflush($fd);
        fclose($fd);
    }

    // Add the crucial data to index
    $workspace->addToIndex("*.txt");

    // Commit the changes
    $workspace->commit("Added some super mandatory file with high security content!");

    // Push everything back to origin
    $workspace->push();

    // If you want you can destroy it...
    // $workspace->destroy();
    // $bare->destroy();
    // Or you examine the folders test.git and test-workspace inside example folder
    // using regular git commands to check if everything has worked as expected.
} catch (GitProviderException $ex) {
    echo $ex->getMessage();
    echo "\n";
    echo $ex->getTraceAsString();
}
