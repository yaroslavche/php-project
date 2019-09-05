<?php

namespace App\Tests;

use App\ComposerEventHandler;
use PHPUnit\Framework\TestCase;

class ComposerEventHandlerTest extends TestCase
{
    public function testPostAutoloadDump()
    {
        $this->assertTrue(true);
        ComposerEventHandler::postAutoloadDump(null);
    }
}
