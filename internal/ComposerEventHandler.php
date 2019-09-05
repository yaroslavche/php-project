<?php

namespace Yaroslavche\PhpProject;

use Composer\Script\Event;

class ComposerEventHandler
{
    public static function runInstall(Event $event): void
    {
        new Install($event);
    }
}
