<?php

namespace App\ServiceProvider;

use App\Service;
use UMA\DIC\Container;
use UMA\DIC\ServiceProvider;

final class Syncplicity implements ServiceProvider
{
    /**
     * Construction du service provider avec un tableau de configuration.
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * Setup PSR11 container's configuration from environment variables.
     */
    public function provide(Container $c) : void
    {
        $c->set(Service\SyncplicityClient::class, fn () => new Service\SyncplicityClient($this->config));
    }
}
