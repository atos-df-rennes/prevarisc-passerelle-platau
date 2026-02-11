<?php

namespace App\ServiceProvider;

use App\Service;
use UMA\DIC\Container;
use UMA\DIC\ServiceProvider;
use App\Service\SyncplicityClient;

final class Platau implements ServiceProvider
{
    private array $config;

    /**
     * Construction du service provider avec un tableau de configuration.
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Setup PSR11 container's configuration from environment variables.
     */
    public function provide(Container $c) : void
    {
        $client = new Service\PlatauClient($this->config);

        if ($c->has(SyncplicityClient::class)) {
            $syncplicity = $c->get(SyncplicityClient::class);
            \assert($syncplicity instanceof SyncplicityClient);
            $client->enableSyncplicity($syncplicity);
        }

        // Création des services Plat'AU
        $c->set('service.platau.consultation', static fn () => $client->consultations);
        $c->set('service.platau.notification', static fn () => $client->notifications);
        $c->set('service.platau.acteur', static fn () => $client->acteurs);
        $c->set('service.platau.piece', static fn () => $client->pieces);
        $c->set('service.platau.healthcheck', static fn () => $client->healthcheck);
        $c->set('service.platau.avis', static fn () => $client->avis);
        $c->set('service.platau.nomenclature', static fn () => $client->nomenclatures);
    }
}
