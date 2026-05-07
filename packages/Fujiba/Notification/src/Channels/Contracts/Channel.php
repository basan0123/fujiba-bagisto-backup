<?php

namespace Fujiba\Notification\Channels\Contracts;

use Fujiba\Notification\Models\NotificationLog;

interface Channel
{
    public function key(): string;

    public function send(NotificationLog $log, string $subject, string $body, array $context = []): void;
}
