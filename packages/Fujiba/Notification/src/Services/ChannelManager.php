<?php

namespace Fujiba\Notification\Services;

use Fujiba\Notification\Channels\Contracts\Channel;

class ChannelManager
{
    /** @var array<string,Channel> */
    protected array $channels = [];

    public function register(Channel $channel): void
    {
        $this->channels[$channel->key()] = $channel;
    }

    public function get(string $key): Channel
    {
        if (! isset($this->channels[$key])) {
            throw new \InvalidArgumentException("Notification channel [{$key}] not registered");
        }

        return $this->channels[$key];
    }

    /** @return array<string,Channel> */
    public function all(): array
    {
        return $this->channels;
    }
}
