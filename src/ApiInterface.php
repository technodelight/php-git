<?php

namespace Technodelight\GitShell;

interface ApiInterface
{
    /**
     * @param string $from
     * @param string $to
     * @return \Generator
     */
    public function log($from, $to = 'head');

    /**
     * @param string $branch
     * @return void
     */
    public function createBranch($branch);

    /**
     * @param string $branch
     * @return void
     */
    public function switchBranch($branch);

    /**
     * @param bool $verbose
     * @return Remote[]
     */
    public function remotes($verbose = false);

    /**
     * @param string $pattern optional
     * @param bool $withRemotes include remotes or not
     * @return Branch[]
     */
    public function branches($pattern = '', $withRemotes = true);

    /**
     * @return Branch|null
     */
    public function currentBranch();

    /**
     * @TODO this often lies, the goal would be to find a branch's first parent
     * @return false|string
     */
    public function parentBranch();

    /**
     * @return string|null
     */
    public function topLevelDirectory();

    /**
     * Get name and status diff for current branch
     *
     * @param string|null $to
     * @return \Technodelight\GitShell\DiffEntry[]
     */
    public function diff($to = null);
}