<?php

namespace Technodelight\GitShell;

class Remote
{
    /**
     * @var string
     */
    private $remote;
    /**
     * @var string
     */
    private $owner;
    /**
     * @var string
     */
    private $repo;
    /**
     * @var string
     */
    private $userHost;
    /**
     * @var string
     */
    private $type;

    public static function fromString($remote)
    {
        $instance = new self;
        $instance->remote = $remote;

        return $instance;
    }

    public static function fromVerboseOutput($remote, $owner, $repo, $userHost, $type)
    {
        $instance = new self;
        $instance->remote = $remote;
        $instance->owner = $owner;
        $instance->repo = $repo;
        $instance->userHost = $userHost;
        $instance->type = $type;

        return $instance;
    }

    /**
     * @return string
     */
    public function remote()
    {
        return $this->remote;
    }

    /**
     * @return string|null
     */
    public function owner()
    {
        return $this->owner;
    }

    /**
     * @return string|null
     */
    public function repo()
    {
        return $this->repo;
    }

    /**
     * @return string|null
     */
    public function userHost()
    {
        return $this->userHost;
    }

    /**
     * @return string|null
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function url()
    {
        if (!empty($this->userHost)) {
            return sprintf('%s:%s/%s.git', $this->userHost, $this->owner, $this->repo);
        }

        return '';
    }
}