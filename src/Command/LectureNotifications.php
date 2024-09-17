<?php

namespace App\Command;

use App\Service\Prevarisc;
use App\Service\PlatauConsultation;
use App\Service\PlatauNotification;
use App\Service\PlatauPiece;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class LectureNotifications extends Command
{
    private PlatauNotification $notification_service;
    private Prevarisc $prevarisc_service;
    private PlatauConsultation $consultation_service;
    private PlatauPiece $piece_service;

    public function __construct(PlatauNotification $notification_service, Prevarisc $prevarisc_service, PlatauConsultation $consultation_service, PlatauPiece $piece_service)
    {
        $this->notification_service = $notification_service;
        $this->prevarisc_service    = $prevarisc_service;
        $this->consultation_service = $consultation_service;
        $this->piece_service        = $piece_service;
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

        if (null !== $offset) {
            if (false === filter_var($offset, \FILTER_VALIDATE_INT)) {
                throw new \Exception(sprintf("La valeur de l'option offset doit être un entier. \"%s\" donné.", $offset));
            }

            $params = ['offset' => $offset];
        }

        $output->writeln('Lecture des nouvelles notifications ...');
        $notifications = $this->notification_service->rechercheNotifications($params);

        if (0 === \count($notifications)) {
            $output->writeln('Aucune nouvelle notification.');

            return Command::SUCCESS;
        }

        foreach ($notifications as $notification) {
            switch ($notification['idTypeObjetMetier']) {
                // Pièce
                case 5:
                    switch ($notification['idTypeEvenement']) {
                        // Pièce complémentaire
                        case 15:
                        // Pièce modificative
                        case 17:
                        // Pièce initiale
                        case 71:
                            $output->writeln(sprintf("Traitement de la notification pour la pièce d'identifiant %s", $notification['idElementConcerne']));

                            try {
                                $pieces = $this->consultation_service->getPieces($notification['idDossier']);
                                $piece_notification = array_filter($pieces, fn ($piece) => $piece['idPiece'] === $notification['idElementConcerne']);
                                $piece_notification = $piece_notification[array_key_first($piece_notification)];

                                // Téléchargement de la pièce
                                $http_response = $this->piece_service->download($piece_notification);

                                // On essaie de trouver l'extension de la pièce jointe
                                $extension = $this->piece_service->getExtensionFromHttpResponse($http_response) ?? '???';

                                // Récupération du contenu de la pièce jointe
                                $file_contents = $http_response->getBody()->getContents();

                                /* Comme la pièce est ajoutée au dossier et non à la consultation,
                                on crée la liaison pour chaque consultation existante */
                                $consultations = $this->consultation_service->rechercheConsultationsAvecCriteresDossier(['idDossier' => $notification['idDossier']]);
                                foreach ($consultations as $consultation) {
                                    if (!$this->prevarisc_service->consultationExiste($consultation['idConsultation'])) {
                                       continue; 
                                    }

                                    // Récupération du dossier Prevarisc lié à cette consultation
                                    $dossier_prevarisc = $this->prevarisc_service->recupererDossierDeConsultation($consultation['idConsultation']);

                                    // Insertion dans Prevarisc
                                    $this->prevarisc_service->creerPieceJointe($dossier_prevarisc['ID_DOSSIER'], $piece_notification, $extension, $file_contents);
                                }
                            } catch (\Exception $e) {
                                $output->writeln(sprintf("La pièce n'a pas pu être téléchargée : %s", $e->getMessage()));
                            }

                            break;
                        default:
                            $output->writeln("La notification de l'événement d'identifiant %d n'est pas prise en compte par la passerelle actuellement.", $notification['idTypeEvenement']);

                            break;
                    }

                    break;
                // Document
                case 31:
                    $output->writeln(sprintf("Traitement de la notification pour le document d'identifiant %s", $notification['idElementConcerne']));

                    switch ($notification['idTypeEvenement']) {
                        // Succès
                        case 84:
                            $output->writeln("Document versé avec succès.");

                            $this->prevarisc_service->changerStatutPiece($notification['idElementConcerne'], 'exported', 'ID_PLATAU');

                            break;
                        // Echec
                        case 85:
                            $output->writeln("Echec du versement du document.");

                            $this->prevarisc_service->changerStatutPiece($notification['idElementConcerne'], 'on_error', 'ID_PLATAU');
                            $this->prevarisc_service->ajouterMessageErreurPiece($notification['idElementConcerne'], $notification['txErreur']);

                            break;
                        default:
                            $output->writeln("La notification de l'événement d'identifiant %d n'est pas prise en compte par la passerelle actuellement.", $notification['idTypeEvenement']);

                            break;
                    }

                    break;
                default:
                    $output->writeln(sprintf("La notification de l'objet métier d'identifiant %d n'est pas prise en compte par la passerelle actuellement.", $notification['idTypeObjetMetier']));

                    break;
            }
        }

        return Command::SUCCESS;
    }
}
