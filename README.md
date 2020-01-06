# php-git
Small PHP wrapper library around `git` shell.

# Install

Via composer.
```
composer require technodelight/php-git
```

# Dependecies

This tool uses `git` through the current active shell it's running in.
There are methods which are dependent on `sed`, `head` and `grep` too.

# Usage

```php
<?php

$git = new Technodelight\GitShell\Api(
    new Technodelight\ShellExec\Exec('/usr/bin/env git')
);

// read git log entries
foreach ($git->log('develop', 'head') as $logEntry) {
    // $logEntry is an instance of Technodelight\GitShell\LogEntry here
}

// create a branch
$git->createBranch('my-precious-branch'); // return is void, but will throw exception on error

// switch to a branch
$git->switchBranch('develop');

// list remotes, optionally verbose mode
$git->remotes(); // runs git remote, returns 'origin' for example
$git->remotes(true); // runs git remote -v, returns a Technodelight\GitShell\Remote instance

// show branches, with optional filter, optionally with remotes
$git->branches();
$git->branches('feature/'); // this uses grep command in the background
$git->branches('', true); // each call will return a Technodelight\GitShell\Branch instance with remote

// retrieve the current branch
$git->currentBranch(); // instance of Technodelight\GitShell\Branch will be returned

// find the top level directory for current directory
$git->topLevelDirectory(); // returns false if not in a git dir, a path (string) otherwise

// show git diff
$git->diff(); // Technodelight\GitShell\DiffEntry object for current changes
$git->diff('develop'); // changes since last commit on develop
```

# License
The MIT License (MIT)

Copyright (c) 2018 Zsolt Gál

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
