<?php

namespace App\Command;

use App\Service\Prevarisc;
use App\Service\PlatauPiece;
use App\Service\PlatauActeur;
use App\Service\PlatauConsultation;
use App\Service\PlatauNomenclature;
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

    private PlatauNomenclature $nomenclature_service;

    public function __construct(
        PlatauNotification $notification_service,
        Prevarisc $prevarisc_service,
        PlatauConsultation $consultation_service,
        PlatauPiece $piece_service,
        PlatauActeur $acteur_service,
        PlatauNomenclature $nomenclature_service,
    ) {
        $this->notification_service = $notification_service;
        $this->prevarisc_service    = $prevarisc_service;
        $this->consultation_service = $consultation_service;
        $this->piece_service        = $piece_service;
        $this->acteur_service       = $acteur_service;
        $this->nomenclature_service = $nomenclature_service;
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
            $identifiant_element_concerne = $notification['idElementConcerne'];
            $objet_metier                 = $this->nomenclature_service->rechercheNomenclature('TYPE_OBJET_METIER', $notification['idTypeObjetMetier']);
            $type_evenement               = $this->nomenclature_service->rechercheNomenclature('TYPE_EVENEMENT', $notification['idTypeEvenement']);

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
                            $idDossier = $notification['idDossier'];

                            $output->writeln($this->logMessage($this->messageTraitementNotification($objet_metier, $type_evenement, $identifiant_element_concerne)));

                            try {
                                $pieces = $this->consultation_service->getPieces($idDossier);

                                if ([] === $pieces) {
                                    throw new \Exception(\sprintf('Aucune pièce trouvée pour le dossier %s', $idDossier));
                                }

                                $piece_notification = array_filter($pieces, static fn ($piece) => $piece['idPiece'] === $identifiant_element_concerne);

                                if ([] === $piece_notification) {
                                    throw new \Exception(\sprintf("La pièce %s n'a pas été trouvée dans les pièces du dossier %s", $identifiant_element_concerne, $idDossier));
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
                            $output->writeln($this->logMessage($this->messageNotificationNonPriseEnCompte($identifiant_element_concerne, $objet_metier, $type_evenement)));

                            break;
                    }

                    break;
                case 6:
                    if (19 !== $notification['idTypeEvenement']) {
                        $output->writeln($this->logMessage($this->messageNotificationNonPriseEnCompte($identifiant_element_concerne, $objet_metier, $type_evenement)));

                        break;
                    }

                    $output->writeln($this->logMessage($this->messageTraitementNotification($objet_metier, $type_evenement, $identifiant_element_concerne)));

                    try {
                        if ($this->prevarisc_service->consultationExiste($identifiant_element_concerne)) {
                            $output->writeln($this->logMessage(\sprintf('Consultation %s déjà existante dans Prevarisc', $identifiant_element_concerne)));

                            break;
                        }

                        $information  = $this->consultation_service->getConsultation($identifiant_element_concerne);
                        $dossier      = $information->getDossier();
                        $consultation = $dossier->getConsultation();

                        $service_instructeur = null !== $dossier->getIdServiceInstructeur() ? $this->acteur_service->recuperationActeur($dossier->getIdServiceInstructeur()) : null;
                        $service_consultant  = null !== $consultation->getIdServiceConsultant() ? $this->acteur_service->recuperationActeur($consultation->getIdServiceConsultant()) : null;
                        $demandeurs          = $dossier->getDemandeurs();

                        // Versement de la consultation dans Prevarisc et on passe l'état de sa PEC à 'awaiting'
                        $this->prevarisc_service->importConsultation($information, $consultation, $service_consultant, $service_instructeur, $demandeurs, $notification);
                        $this->prevarisc_service->setMetadonneesEnvoi($identifiant_element_concerne, 'PEC', 'awaiting')->executeStatement();

                        $output->writeln($this->logMessage(\sprintf('Consultation %s récupérée et stockée dans Prevarisc !', $identifiant_element_concerne)));

                        // Récupération du dossier Prevarisc nouvellement créé
                        $dossier_prevarisc = $this->prevarisc_service->recupererDossierDeConsultation($identifiant_element_concerne);

                        // Téléchargement des pièces initiales
                        $pieces = $this->consultation_service->getPieces($notification['idDossier']);
                        foreach ($pieces as $piece) {
                            $http_response = $this->piece_service->download($piece);
                            $extension     = $this->piece_service->getExtensionFromHttpResponse($http_response) ?? '???';
                            $file_contents = $http_response->getBody()->getContents();

                            $this->prevarisc_service->creerPieceJointe($dossier_prevarisc['ID_DOSSIER'], $piece, $extension, $file_contents, $notification);
                        }

                        $output->writeln($this->logMessage(\sprintf('Pièces initiales importées pour la consultation %s', $identifiant_element_concerne)));
                    } catch (\Exception $e) {
                        $output->writeln($this->logMessage(\sprintf('[Offset: %d] Problème lors du traitement de la consultation : %s', $notification['offset'], $e->getMessage())));
                    }

                    break;
                case 31:
                    $output->writeln($this->logMessage($this->messageTraitementNotification($objet_metier, $type_evenement, $identifiant_element_concerne)));

                    /* 84 - Succès
                       85 - Echec */
                    switch ($notification['idTypeEvenement']) {
                        case 84:
                            $output->writeln($this->logMessage('Document versé avec succès.'));

                            $this->prevarisc_service->changerStatutPiece($identifiant_element_concerne, 'exported', 'ID_PLATAU');

                            break;
                        case 85:
                            $error_message = $notification['txErreur'] ?? 'Erreur inconnue';
                            $output->writeln($this->logMessage('Echec du versement du document : '.$error_message));

                            // Extraire le code d'erreur si présent dans le message
                            $error_code = PlatauNotification::extractErrorCodeFromErrorMessage($error_message);
                            if (9 !== $error_code) {
                                $this->prevarisc_service->changerStatutPiece($identifiant_element_concerne, 'on_error', 'ID_PLATAU');
                                $this->prevarisc_service->ajouterMessageErreurPiece($identifiant_element_concerne, $notification['txErreur'], 'ID_PLATAU');

                                break;
                            }

                            $output->writeln($this->logMessage('CODE 9 détecté : La pièce va être marquée pour renvoi.'));

                            $consultation_associee = $this->prevarisc_service->recupererConsultationDePiece($identifiant_element_concerne);
                            if (false === $consultation_associee) {
                                $output->writeln($this->logMessage(\sprintf("Impossible d'identifier la consultation associée à la pièce %s.", $identifiant_element_concerne)));

                                break;
                            }

                            $objet_metier = PlatauNotification::identifierObjetMetier($consultation_associee);
                            if (null === $objet_metier) {
                                $output->writeln($this->logMessage(\sprintf("Impossible d'identifier l'objet métier associé au renvoi de la pièce %s.", $identifiant_element_concerne)));

                                break;
                            }

                            $this->prevarisc_service->setMetadonneesEnvoi($consultation_associee['ID_PLATAU'], $objet_metier, 'to_export')->executeStatement();
                            $this->prevarisc_service->changerStatutPiece($identifiant_element_concerne, 'to_be_exported', 'ID_PLATAU');

                            $output->writeln($this->logMessage(\sprintf("La pièce %s a été marquée pour renvoi. La consultation associée %s a été marquée pour renvoi avec l'objet métier %s", $identifiant_element_concerne, $consultation_associee['ID_PLATAU'], $objet_metier)));

                            break;
                        default:
                            $output->writeln($this->logMessage($this->messageNotificationNonPriseEnCompte($identifiant_element_concerne, $objet_metier, $type_evenement)));

                            break;
                    }

                    break;
                default:
                    $output->writeln($this->logMessage($this->messageNotificationNonPriseEnCompte($identifiant_element_concerne, $objet_metier)));

                    break;
            }
        }

        return Command::SUCCESS;
    }

    // Log les messsages avec la date et l'heure à la manière de Monolog.
    private function logMessage(string $message) : string
    {
        return \sprintf('[%s] %s', (new \DateTime())->format('d-m-Y H:i:s'), $message);
    }

    // Affiche un message d'information pour les notifications que la passerelle ne traite pas.
    private function messageNotificationNonPriseEnCompte(string $id_element_concerne, string $objet_metier, ?string $type_evenement = null) : string
    {
        $message = 'La notification %s';
        $values  = [$objet_metier];

        if (null !== $type_evenement) {
            $message .= ' - %s';
            $values[] = $type_evenement;
        }

        $values[] = $id_element_concerne;

        $message .= " pour l'élément d'identifiant %s n'est pas prise en compte par la passerelle actuellement";

        return \sprintf($message, ...$values);
    }

    // Affiche un message d'information pour les notifications que la passerelle traite.
    private function messageTraitementNotification(string $objet_metier, string $type_evenement, string $identifiant_element_concerne) : string
    {
        return vsprintf('Traitement de la notification %s - %s pour l\'élément d\'identifiant %s', [
            $objet_metier,
            $type_evenement,
            $identifiant_element_concerne,
        ]);
    }
}
