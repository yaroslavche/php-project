<?php

namespace App\Tests;

use App\ComposerEventHandler;
use PHPUnit\Framework\TestCase;

class ComposerEventHandlerTest extends TestCase
{
    public function testPostInstall()
    {
        $this->assertTrue(true);
        ComposerEventHandler::postInstall(null);
    }
}
