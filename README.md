[![Build Status](https://travis-ci.org/maikgreubel/phpgitprovider.svg)](https://travis-ci.org/maikgreubel/phpgitprovider)
[![Coverage Status](https://coveralls.io/repos/maikgreubel/phpgitprovider/badge.svg)](https://coveralls.io/r/maikgreubel/phpgitprovider)

phpGitProvider
==

This package provides an easy to use git client class for PHP. Here a small example to show the features:

```php
$path = "/path/to/where/you/want/to/work/on/your/repo";

$provider = new GitProvider($path);

// Create a shared bare repository
$provider->create(true, true);
// Set the author of the repo
$provider->setAuthor("John Doe", "john@doe.tld");
// Provide a project name
$provider->setProjectName('A test repository');

// Some data to repository (file must exist)
$provider->addToIndex("README.md");

// Commit your changes with a speaking message
$provider->commit("Added README");
```