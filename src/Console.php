<?php

namespace App;

use UMA\DIC\Container;
use Symfony\Component\Console\Application;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class Console extends Application
{
    public function __construct(array $config = [])
    {
        // Version de l'application
        $version = 'dev';

        // Construction de l'application console
        parent::__construct('Passerelle Prevarisc PlatAU', $version);

        // On cadre les options que la passerelle attend (les requis, et les optionnels)
        $resolver = new OptionsResolver();
        $resolver->setRequired(['prevarisc.options', 'platau.options']);
        $resolver->setDefaults(['syncplicity.options' => null]);
        $config = $resolver->resolve($config);

        // Création d'un Container PSR-11 pour exposer des objets / configurations de façon standardisée
        $container = new Container();
        $container->register(new ServiceProvider\Prevarisc($config['prevarisc.options']));
        if (null !== $config['syncplicity.options']) {
            $container->register(new ServiceProvider\Syncplicity($config['syncplicity.options']));
        }
        $container->register(new ServiceProvider\Platau($config['platau.options']));

        // Enregistrement des commandes disponibles
        $this->add(new Command\Healthcheck($container->get('service.platau.healthcheck'), $container->get('service.prevarisc')));
        $this->add(new Command\ImportPieces($container->get('service.prevarisc'), $container->get('service.platau.consultation'), $container->get('service.platau.piece')));
        $this->add(new Command\ImportConsultations($container->get('service.prevarisc'), $container->get('service.platau.consultation'), $container->get('service.platau.acteur')));
        $this->add(new Command\ExportPEC($container->get('service.prevarisc'), $container->get('service.platau.consultation'), $container->get('service.platau.piece')));
        $this->add(new Command\ExportAvis($container->get('service.prevarisc'), $container->get('service.platau.consultation'), $container->get('service.platau.piece'), $container->get('service.platau.avis')));
        $this->add(new Command\EnrolerActeur($container->get('service.platau.acteur')));
        $this->add(new Command\DetailsConsultation($container->get('service.platau.consultation')));
        $this->add(new Command\DetailsAvis($container->get('service.platau.avis')));
        $this->add(new Command\LectureNotifications($container->get('service.platau.notification'), $container->get('service.prevarisc'), $container->get('service.platau.consultation'), $container->get('service.platau.piece'), $container->get('service.platau.acteur')));
    }
}
