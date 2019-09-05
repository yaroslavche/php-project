<?php

namespace Yaroslavche\PhpProject;

use Composer\Script\Event;

class ComposerEventHandler
{
    public static function postAutoloadDump(Event $event): void
    {
    }

    public static function postInstall(Event $event): void
    {
        new Install($event);
    }

    public static function postCreateProject(Event $event): void
    {
    }
}
