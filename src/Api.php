<?php

namespace Technodelight\GitShell;

use Technodelight\ShellExec\Command;
use Technodelight\ShellExec\Shell;
use Technodelight\ShellExec\ShellCommandException;

class Api implements ApiInterface
{
    const LOG_FORMAT = '"<entry><hash><![CDATA[%H]]></hash><message><![CDATA[%B]]></message><authorName>%aN</authorName><authorDate>%at</authorDate></entry>"';
    const VERBOSE_REMOTE_REGEX = '~([a-z0-9]+)\s+([^:]+):([^/]+)/(.*).git \((fetch|push)\)~';
    /**
     * @var Shell
     */
    private $shell;
    /**
     * @var Remote[]
     */
    private $remotes;
    /**
     * @var Remote[]
     */
    private $verboseRemotes;
    /**
     * @var string|null
     */
    private $tld = null;

    public function __construct(Shell $shell)
    {
        $this->shell = $shell;
    }

    /**
     * @param string $from
     * @param string $to
     * @return \Generator
     */
    public function log($from, $to = 'head')
    {
        $command = Command::create()
            ->withArgument('log')
            ->withOption('format', self::LOG_FORMAT)
            ->withOption('no-merges')
            ->withOption('date-order')
            ->withOption('reverse')
            ->withArgument(sprintf('%s..%s', $from, $to));

        $converter = new XmlToArray('entry');
        $entries = $converter->asArray(implode(PHP_EOL, $this->shell->exec($command)));
        if (!empty($entries['entry'])) foreach ($entries['entry'] as $entry) {
            yield LogEntry::fromArray($entry);
        }
    }

    /**
     * @param string $branch
     * @return void
     */
    public function createBranch($branch)
    {
        $this->shell->exec(Command::create()->withArgument('checkout')->withOption('b')->withArgument($branch));
    }

    /**
     * @param string $branch
     * @return void
     */
    public function switchBranch($branch)
    {
        $this->shell->exec(Command::create()->withArgument('checkout')->withArgument($branch));
    }

    /**
     * @param bool $verbose
     * @return Remote[]
     */
    public function remotes($verbose = false)
    {
        if ($verbose) {
            if (!$this->verboseRemotes) {
                $remotesDef = $this->shell->exec(Command::create()->withArgument('remote')->withOption('v')->withStdErrTo('/dev/null'));
                $this->verboseRemotes = [];
                foreach ($remotesDef as $def) {
                    if (preg_match(self::VERBOSE_REMOTE_REGEX, trim($def), $matches)) {
                        list (,$remote, $userHost, $owner, $repo, $type) = $matches;

                        $this->verboseRemotes[] = Remote::fromVerboseOutput(
                            $remote,
                            $owner,
                            $repo,
                            $userHost,
                            $type
                        );
                    }
                }
                if (!$this->verboseRemotes) {
                    throw new \RuntimeException('No git remote found!');
                }
            }
            return $this->verboseRemotes;
        }
        if (!$this->remotes) {
            $this->remotes = array_map(
                function ($remote) {
                    return Remote::fromString($remote);
                },
                $this->shell->exec(Command::create()->withArgument('remote')->withStdErrTo('/dev/null'))
            );
        }
        return $this->remotes;
    }

    /**
     * @param string $pattern optional
     * @param bool $withRemotes include remotes or not
     * @return Branch[]
     */
    public function branches($pattern = '', $withRemotes = true)
    {
        $command = Command::create()->withArgument('branch');
        if ($withRemotes) {
            $command->withOption('a');
        }

        if ($pattern) {
            $command->pipe(
                Command::create('grep')->withArgument(escapeshellarg($pattern))
            );
        }
        try {
            $branches = $this->shell->exec($command);
        } catch (ShellCommandException $e) {
            // ignore exception when grep returns "no lines", return code 1
            if ($pattern && $e->getCode() == 1) {
                $branches = $e->getResult();
            } else {
                throw $e;
            }
        }
        $remoteNames = array_unique(array_map(
            function(Remote $remote) {
                return $remote->remote();
            },
            $this->remotes(true)
        ));
        return array_map(
            function($branchDef) use ($remoteNames) {
                $current = false;
                $remote = '';

                if (preg_match('~(' . join('|', $remoteNames) . ')/([^/]+)/~', $branchDef, $matches)) {
                    $remote = $matches[1];
                }
                if (strpos($branchDef, '* ') !== false) {
                    $current = true;
                }

                return Branch::fromArray([
                    'name' => str_replace(['remotes/'.$remote.'/', '* '], '', $branchDef),
                    'current' => $current,
                    'remote' => $remote,
                ]);
            },
            $branches
        );
    }

    /**
     * @return Branch|null
     */
    public function currentBranch()
    {
        $list = $this->branches('* ');
        foreach ($list as $branch) {
            if ($branch->current()) {
                return $branch;
            }
        }

        return null;
    }

    /**
     * @TODO this often lies, the goal would be to find a branch's first parent
     * @return false|string
     */
    public function parentBranch()
    {
        // git log --decorate --simplify-by-decoration | grep '^commit' | egrep -o '\([^)]+\)' | head -2 | tail -1 | sed -E "s~[\(\)]+~~g" | tr , \n | tail -1
        $result = $this->shell->exec(
            Command::create()
                ->withArgument('log')
                ->withOption('decorate')
                ->withOption('simplify-by-decoration')
            ->pipe(
                Command::create('grep')->withArgument("'^commit'")
            )
            ->pipe(
                Command::create('egrep')->withShortOption('o')->withArgument("'\([^)]+\)'")
            )
            ->pipe(
                Command::create('head')->withShortOption('2')
            )
            ->pipe(
                Command::create('tail')->withShortOption('1')
            )
            ->pipe(
                Command::create('sed')->withArgument("'s~[\(\)]+~~g'")
            )
        );
        $parents = array_map('trim', explode(',', array_shift($result)));

        return end($parents);
    }

    /**
     * @return string|null
     */
    public function topLevelDirectory()
    {
        if (null === $this->tld) {
            $tld = $this->shell->exec(
                Command::create()
                    ->withArgument('rev-parse')
                    ->withOption('show-toplevel')
                    ->withStdErrTo('/dev/null')
            );
            $this->tld = end($tld);
        }
        return $this->tld;
    }

    /**
     * Get name and status diff for current branch
     *
     * @param string|null $to
     * @return \Technodelight\GitShell\DiffEntry[]
     */
    public function diff($to = null)
    {
        return array_map(
            function($row) {
                return DiffEntry::fromString($row);
            },
            $this->shell->exec(
                Command::create()
                       ->withArgument('diff')->withArgument($to)
                       ->withOption('name-status')
            )
        );
    }
}
