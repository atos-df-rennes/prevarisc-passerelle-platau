<?php

namespace App\Command;

use App\Service\PlatauAvis;
use App\Service\PlatauPiece;
use App\ValueObjects\Auteur;
use App\Service\Prevarisc as PrevariscService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\PlatauConsultation as PlatauConsultationService;

final class ExportAvis extends Command
{
    private PrevariscService $prevarisc_service;
    private PlatauConsultationService $consultation_service;
    private PlatauPiece $piece_service;
    private PlatauAvis $avis_service;

    /**
     * Initialisation de la commande.
     */
    public function __construct(PrevariscService $prevarisc_service, PlatauConsultationService $consultation_service, PlatauPiece $piece_service, PlatauAvis $avis_service)
    {
        $this->prevarisc_service    = $prevarisc_service;
        $this->consultation_service = $consultation_service;
        $this->piece_service        = $piece_service;
        $this->avis_service         = $avis_service;
        parent::__construct();
    }

    /**
     * Configuration de la commande.
     */
    protected function configure()
    {
        $this->setName('export-avis')
            ->setDescription("Exporte un avis Prevarisc sur Plat'AU.")
            ->addOption('consultation-id', null, InputOption::VALUE_OPTIONAL, 'Consultation concernée')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Chemin vers le fichier de configuration');
    }

    /**
     * Logique d'execution de la commande.
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        // Si l'utilisateur demande de traiter une consultation en particulier, on s'occupe de celle là.
        // Sinon, l'utilisateur demande de traiter les consultations en attente d'avis
        // Sinon on récupère dans Plat'AU l'ensemble des consultations en attente d'avis (c'est à dire avec un état "Prise en compte - en cours de traitement") et celle déjà traitées
        if ($input->getOption('consultation-id')) {
            $output->writeln('Récupération de la consultation concernée ...');
            $consultations_en_attente_davis = [$this->consultation_service->getConsultation($input->getOption('consultation-id'))];
        } else {
            $output->writeln('Recherche de toutes les consultations en attente d\'avis ou traitées (à renvoyer) ...');

            $consultations_a_renvoyer = $this->prevarisc_service->recupererDossiersARenvoyer();
            $consultations_a_renvoyer = array_map(
                fn ($consultation_id) => $this->consultation_service->getConsultation($consultation_id),
                $consultations_a_renvoyer
            );

            $consultations_en_attente_davis = $this->consultation_service->rechercheConsultations(['nomEtatConsultation' => [3]]);
            $consultations_en_attente_davis = array_merge($consultations_a_renvoyer, $consultations_en_attente_davis);
        }

        // Si il n'existe pas de consultations en attente d'avis, on arrête le travail ici
        if (empty($consultations_en_attente_davis)) {
            $output->writeln('Pas de consultations en attente d\'avis.');

            return Command::SUCCESS;
        }

        // Pour chaque consultation trouvée, on va chercher dans Prevarisc si un avis existe.
        foreach ($consultations_en_attente_davis as $consultation) {
            // Récupération de l'ID de la consultation
            $consultation_id = $consultation['idConsultation'];

            // On essaie d'envoyer l'avis sur Plat'AU
            try {
                $pieces_to_export = [];
                $pieces           = [];

                // Vérification de l'existence de la consultation dans Prevarisc ? Si non, on ignore complètement la consultation
                if (!$this->prevarisc_service->consultationExiste($consultation_id)) {
                    $output->writeln("La consultation $consultation_id n'existe pas dans Prevarisc. Importez là d'abord avec la commande <import>.");
                    continue;
                }

                // Récupération du dossier dans Prevarisc
                $dossier = $this->prevarisc_service->recupererDossierDeConsultation($consultation_id);
                $auteur  = $this->prevarisc_service->recupererDossierAuteur($dossier['ID_DOSSIER']);

                // Nom état consultation 6 = Consultation traitée
                if (6 === $consultation['nomEtatConsultation']['idNom'] && !\in_array($dossier['STATUT_AVIS'], ['to_export', 'in_error'])) {
                    continue;
                }

                // On recherche les prescriptions associées au dossier Prevarisc
                $prescriptions = $this->prevarisc_service->getPrescriptions($dossier['ID_DOSSIER']);

                // On recherche les pièces jointes en attente d'envoi vers Plat'AU associées au dossier Prevarisc
                if ($this->piece_service->getSyncplicity()) {
                    $pieces_to_export = $this->prevarisc_service->recupererPiecesAvecStatut($dossier['ID_DOSSIER'], 'to_be_exported');

                    foreach ($pieces_to_export as $piece_jointe) {
                        $filename = $piece_jointe['NOM_PIECEJOINTE'].$piece_jointe['EXTENSION_PIECEJOINTE'];
                        $contents = $this->prevarisc_service->recupererFichierPhysique($piece_jointe['ID_PIECEJOINTE'], $piece_jointe['EXTENSION_PIECEJOINTE']);

                        try {
                            $pieces[] = $this->piece_service->uploadDocument($filename, $contents, 9); // Type document 9 = Document lié à un avis
                            $this->prevarisc_service->changerStatutPiece($piece_jointe['ID_PIECEJOINTE'], 'awaiting_status');
                        } catch (\Exception $e) {
                            $this->prevarisc_service->changerStatutPiece($piece_jointe['ID_PIECEJOINTE'], 'on_error');
                            $this->prevarisc_service->ajouterMessageErreurPiece($piece_jointe['ID_PIECEJOINTE'], $e->getMessage());
                        }
                    }
                }

                // On verse l'avis de commission Prevarisc (défavorable ou favorable à l'étude) dans Plat'AU
                if ('1' === (string) $dossier['AVIS_DOSSIER_COMMISSION'] || '2' === (string) $dossier['AVIS_DOSSIER_COMMISSION']) {
                    // On verse l'avis de commission dans Plat'AU
                    // Pour rappel, un avis de commission à 1 = favorable, 2 = défavorable.
                    $est_favorable = '1' === (string) $dossier['AVIS_DOSSIER_COMMISSION'];
                    $output->writeln("Versement d'un avis ".($est_favorable ? 'favorable' : 'défavorable')." pour la consultation $consultation_id au service instructeur ...");
                    // Si cela concerne un premier envoi d'avis alors on place la date de l'avis Prevarisc, sinon la date du lancement de la commande
                    $date_envoi = new \DateTime();

                    if ('to_export' === $dossier['STATUT_AVIS']) {
                        $avis       = $this->avis_service->getAvisForConsultation($consultation_id);
                        $date_envoi = null !== $dossier['DATE_AVIS'] ? \DateTime::createFromFormat('Y-m-d', $dossier['DATE_AVIS']) : \DateTime::createFromFormat('Y-m-d', $avis['dtAvis']);
                    }

                    $avis_verse = $this->consultation_service->versementAvis(
                        $consultation_id,
                        $est_favorable,
                        $prescriptions,
                        $pieces,
                        $date_envoi,
                        new Auteur($auteur['PRENOM_UTILISATEURINFORMATIONS'], $auteur['NOM_UTILISATEURINFORMATIONS'], $auteur['MAIL_UTILISATEURINFORMATIONS'], $auteur['TELFIXE_UTILISATEURINFORMATIONS'], $auteur['TELPORTABLE_UTILISATEURINFORMATIONS']),
                    );
                    $avis_verse_data = json_decode($avis_verse->getBody()->getContents(), true, 512, \JSON_THROW_ON_ERROR);
                    $avis_documents  = $avis_verse_data[array_key_first($avis_verse_data)]['avis'][0]['documents'];

                    foreach ($pieces_to_export as $index_piece => $piece_to_map) {
                        if (!\array_key_exists($index_piece, $avis_documents)) {
                            $filename = $piece_to_map['NOM_PIECEJOINTE'].$piece_to_map['EXTENSION_PIECEJOINTE'];
                            $output->writeln("La pièce {$filename} n'a pas été trouvée dans la liste des documents envoyés avec l'avis");

                            continue;
                        }

                        $id_document = $avis_documents[$index_piece]['idDocument'];

                        $this->prevarisc_service->setPieceIdPlatau($piece_to_map['ID_PIECEJOINTE'], $id_document);
                    }

                    $this->prevarisc_service->setMetadonneesEnvoi($consultation_id, 'AVIS', 'treated')->set('DATE_AVIS', ':date_avis')->setParameter('date_avis', date('Y-m-d'))->executeStatement();
                    $output->writeln('Avis envoyé !');
                } else {
                    $output->writeln("Impossible d'envoyer un avis pour la consultation $consultation_id pour le moment (en attente de l'avis de commission dans Prevarisc) ...");
                }
            } catch (\Exception $e) {
                // On passe toutes les pièces en attente de versement
                foreach ($pieces_to_export as $piece) {
                    if ('on_error' !== $piece['NOM_STATUT']) {
                        $this->prevarisc_service->changerStatutPiece($piece['ID_PIECEJOINTE'], 'to_be_exported');
                    }
                }

                // On passe la consultation en erreur dans Prevarisc
                $this->prevarisc_service->setMetadonneesEnvoi($consultation_id, 'AVIS', 'in_error')->executeStatement();

                $output->writeln("Problème lors du versement de l'avis : {$e->getMessage()}");
            }
        }

        return Command::SUCCESS;
    }
}
