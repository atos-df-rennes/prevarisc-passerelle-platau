<?php

namespace App\Command;

use App\Service\PlatauNotification;
use App\Service\Prevarisc;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class LectureNotifications extends Command
{
    private PlatauNotification $notification_service;
    private Prevarisc $prevarisc_service;

    public function __construct(PlatauNotification $notification_service, Prevarisc $prevarisc_service)
    {
        $this->notification_service = $notification_service;
        $this->prevarisc_service = $prevarisc_service;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('lecture-notifications')
            ->setDescription("Lit les notifications qui n'ont pas encore été consommées.")
            ->addOption('offset', null, InputOption::VALUE_REQUIRED, 'Offset à partir duquel on récupère les notifications')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Chemin vers le fichier de configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $offset = $input->getOption('offset');
        $params = [];

        if ($offset !== null) {
            if (filter_var($offset, FILTER_VALIDATE_INT) === false) {
                throw new \Exception(sprintf("La valeur de l'option offset doit être un entier. \"%s\" donné.", $offset));
            }

            $params = ['offset' => $offset];
        }

        $output->writeln("Lecture des nouvelles notifications ...");
        $notifications = $this->notification_service->rechercheNotifications($params);

        if (count($notifications) === 0) {
            $output->writeln("Aucune nouvelle notification.");

            return Command::SUCCESS;
        }

        foreach ($notifications as $notification) {
            if ($notification['idTypeObjetMetier'] !== 31) { // Type objet métier 31 = Document
                continue;
            }

            $output->writeln(sprintf("Traitement de la notification pour le document d'identifiant %s", $notification['idElementConcerne']));
            
            if ($notification['idTypeEvenement'] === 84) {
                $this->prevarisc_service->changerStatutPiece($notification['idElementConcerne'], 'exported', 'ID_PLATAU');
            }
            if ($notification['idTypeEvenement'] === 85) {
                $this->prevarisc_service->changerStatutPiece($notification['idElementConcerne'], 'on_error', 'ID_PLATAU');
                $this->prevarisc_service->ajouterMessageErreurPiece($notification['idElementConcerne'], $notification['txErreur']);
            }
        }

        return Command::SUCCESS;
    }
}