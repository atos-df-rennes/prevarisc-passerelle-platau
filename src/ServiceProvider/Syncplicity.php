<?php

namespace App\ServiceProvider;

use UMA\DIC\Container;
use UMA\DIC\ServiceProvider;
use App\Service\SyncplicityClient;

final readonly class Syncplicity implements ServiceProvider
{
    /**
     * Construction du service provider avec un tableau de configuration.
     */
    public function __construct(private array $config)
    {
    }

    /**
     * Setup PSR11 container's configuration from environment variables.
     */
    public function provide(Container $c) : void
    {
        $c->set(SyncplicityClient::class, fn () : SyncplicityClient => new SyncplicityClient($this->config));
    }
}
