<?php

declare(strict_types=1);

namespace Yaroslavche\PhpProject;

use Composer\Script\Event;

/** @internal */
final class ComposerEventHandler
{
    public static function runInstall(Event $event): void
    {
        new Install($event);
    }
}
