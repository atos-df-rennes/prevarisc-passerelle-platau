<?php

namespace App\Command;

use App\Service\Prevarisc;
use App\Service\PlatauPiece;
use App\Service\PlatauActeur;
use App\Service\PlatauConsultation;
use App\Service\PlatauNotification;
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
    private PlatauActeur $acteur_service;

    public function __construct(
        PlatauNotification $notification_service,
        Prevarisc $prevarisc_service,
        PlatauConsultation $consultation_service,
        PlatauPiece $piece_service,
        PlatauActeur $acteur_service,
    ) {
        $this->notification_service = $notification_service;
        $this->prevarisc_service    = $prevarisc_service;
        $this->consultation_service = $consultation_service;
        $this->piece_service        = $piece_service;
        $this->acteur_service       = $acteur_service;
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
                throw new \Exception(\sprintf("La valeur de l'option offset doit être un entier. \"%s\" donné.", $offset));
            }

            $params = ['offset' => $offset];
        }

        $output->writeln($this->logMessage('Lecture des nouvelles notifications ...'));
        $notifications = $this->notification_service->rechercheNotifications($params);

        if (0 === \count($notifications)) {
            $output->writeln($this->logMessage('Aucune nouvelle notification.'));

            return Command::SUCCESS;
        }

        foreach ($notifications as $notification) {
            /* 5 - Pièce
               6 - Consultation
               31 - Document */
            switch ($notification['idTypeObjetMetier']) {
                case 5:
                    /* 15 - Complémentaire
                       17 - Modificative
                       71 - Initiale */
                    switch ($notification['idTypeEvenement']) {
                        case 15:
                        case 17:
                        case 71:
                            $idPiece   = $notification['idElementConcerne'];
                            $idDossier = $notification['idDossier'];

                            $output->writeln($this->logMessage(\sprintf("Traitement de la notification pour la pièce d'identifiant %s", $idPiece)));

                            try {
                                $pieces = $this->consultation_service->getPieces($idDossier);

                                if ([] === $pieces) {
                                    throw new \Exception(\sprintf('Aucune pièce trouvée pour le dossier %s', $idDossier));
                                }

                                $piece_notification = array_filter($pieces, fn ($piece) => $piece['idPiece'] === $idPiece);

                                if ([] === $piece_notification) {
                                    throw new \Exception(\sprintf("La pièce %s n'a pas été trouvée dans les pièces du dossier %s", $idPiece, $idDossier));
                                }

                                $piece_notification = $piece_notification[array_key_first($piece_notification)];

                                // Téléchargement de la pièce
                                $http_response = $this->piece_service->download($piece_notification);

                                // On essaie de trouver l'extension de la pièce jointe
                                $extension = $this->piece_service->getExtensionFromHttpResponse($http_response) ?? '???';

                                // Récupération du contenu de la pièce jointe
                                $file_contents = $http_response->getBody()->getContents();

                                /* Comme la pièce est ajoutée au dossier et non à la consultation,
                                on crée la liaison pour chaque consultation existante */
                                $consultations = $this->consultation_service->rechercheConsultationsAvecCriteresDossier(['idDossier' => $idDossier]);
                                foreach ($consultations as $consultation) {
                                    if (!$this->prevarisc_service->consultationExiste($consultation['idConsultation'])) {
                                        continue;
                                    }

                                    // Récupération du dossier Prevarisc lié à cette consultation
                                    $dossier_prevarisc = $this->prevarisc_service->recupererDossierDeConsultation($consultation['idConsultation']);

                                    // Insertion dans Prevarisc
                                    $this->prevarisc_service->creerPieceJointe($dossier_prevarisc['ID_DOSSIER'], $piece_notification, $extension, $file_contents, $notification);
                                }

                                $output->writeln($this->logMessage('La pièce a été téléchargée.'));
                            } catch (\Exception $e) {
                                $output->writeln($this->logMessage(\sprintf("[Offset: %d] La pièce n'a pas pu être téléchargée : %s", $notification['offset'], $e->getMessage())));
                            }

                            break;
                        default:
                            $output->writeln($this->logMessage(\sprintf("La notification de l'événement d'identifiant %d n'est pas prise en compte par la passerelle actuellement.", $notification['idTypeEvenement'])));

                            break;
                    }

                    break;
                case 6:
                    if (19 !== $notification['idTypeEvenement']) {
                        $output->writeln($this->logMessage(\sprintf("La notification de l'événement d'identifiant %d n'est pas prise en compte par la passerelle actuellement.", $notification['idTypeEvenement'])));

                        break;
                    }

                    $output->writeln($this->logMessage(\sprintf("Traitement de la notification pour la consultation d'identifiant %s", $notification['idElementConcerne'])));

                    try {
                        $consultation_id = $notification['idElementConcerne'];

                        if ($this->prevarisc_service->consultationExiste($consultation_id)) {
                            $output->writeln($this->logMessage(\sprintf('Consultation %s déjà existante dans Prevarisc', $consultation_id)));

                            break;
                        }

                        $information  = $this->consultation_service->getConsultation($consultation_id);
                        $dossier      = $information->getDossier();
                        $consultation = $dossier->getConsultation();

                        $service_instructeur = null !== $dossier->getIdServiceInstructeur() ? $this->acteur_service->recuperationActeur($dossier->getIdServiceInstructeur()) : null;
                        $demandeur           = null !== $consultation->getIdServiceConsultant() ? $this->acteur_service->recuperationActeur($consultation->getIdServiceConsultant()) : null;

                        // Versement de la consultation dans Prevarisc et on passe l'état de sa PEC à 'awaiting'
                        $this->prevarisc_service->importConsultation($information, $demandeur, $service_instructeur, $notification);
                        $this->prevarisc_service->setMetadonneesEnvoi($consultation_id, 'PEC', 'awaiting')->executeStatement();

                        $output->writeln($this->logMessage(\sprintf('Consultation %s récupérée et stockée dans Prevarisc !', $consultation_id)));

                        // Récupération du dossier Prevarisc nouvellement créé
                        $dossier_prevarisc = $this->prevarisc_service->recupererDossierDeConsultation($consultation_id);

                        // Téléchargement des pièces initiales
                        $pieces = $this->consultation_service->getPieces($notification['idDossier']);
                        foreach ($pieces as $piece) {
                            $http_response = $this->piece_service->download($piece);
                            $extension     = $this->piece_service->getExtensionFromHttpResponse($http_response) ?? '???';
                            $file_contents = $http_response->getBody()->getContents();

                            $this->prevarisc_service->creerPieceJointe($dossier_prevarisc['ID_DOSSIER'], $piece, $extension, $file_contents, $notification);
                        }

                        $output->writeln($this->logMessage(\sprintf('Pièces initiales importées pour la consultation %s', $consultation_id)));
                    } catch (\Exception $e) {
                        $output->writeln($this->logMessage(\sprintf('[Offset: %d] Problème lors du traitement de la consultation : %s', $notification['offset'], $e->getMessage())));
                    }

                    break;
                case 31:
                    $output->writeln($this->logMessage(\sprintf("Traitement de la notification pour le document d'identifiant %s", $notification['idElementConcerne'])));

                    /* 84 - Succès
                       85 - Echec */
                    switch ($notification['idTypeEvenement']) {
                        case 84:
                            $output->writeln($this->logMessage('Document versé avec succès.'));

                            $this->prevarisc_service->changerStatutPiece($notification['idElementConcerne'], 'exported', 'ID_PLATAU');

                            break;
                        case 85:
                            $output->writeln($this->logMessage('Echec du versement du document.'));

                            $this->prevarisc_service->changerStatutPiece($notification['idElementConcerne'], 'on_error', 'ID_PLATAU');
                            $this->prevarisc_service->ajouterMessageErreurPiece($notification['idElementConcerne'], $notification['txErreur'], 'ID_PLATAU');

                            break;
                        default:
                            $output->writeln($this->logMessage(\sprintf("La notification de l'événement d'identifiant %d n'est pas prise en compte par la passerelle actuellement.", $notification['idTypeEvenement'])));

                            break;
                    }

                    break;
                default:
                    $output->writeln($this->logMessage(\sprintf("La notification de l'objet métier d'identifiant %d n'est pas prise en compte par la passerelle actuellement.", $notification['idTypeObjetMetier'])));

                    break;
            }
        }

        return Command::SUCCESS;
    }

    private function logMessage(string $message) : string
    {
        return \sprintf('[%s] %s', (new \DateTime())->format('d-m-Y H:i:s'), $message);
    }
}
