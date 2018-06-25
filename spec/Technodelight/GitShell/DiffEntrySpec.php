<?php

namespace spec\Technodelight\GitShell;

use PhpSpec\ObjectBehavior;

class DiffEntrySpec extends ObjectBehavior
{
    function it_can_be_created_from_string()
    {
        $this->beConstructedFromString('M       features/bootstrap/configs/api.xml');
        $this->state()->shouldReturn('M');
        $this->file()->shouldReturn('features/bootstrap/configs/api.xml');
    }
}
