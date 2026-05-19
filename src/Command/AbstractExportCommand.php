<?php

namespace App\Command;

use App\Service\PlatauPiece;
use App\Service\Prevarisc as PrevariscService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractExportCommand extends Command
{
    protected PrevariscService $prevarisc_service;
    protected PlatauPiece $piece_service;

    public function __construct(PrevariscService $prevarisc_service, PlatauPiece $piece_service)
    {
        $this->prevarisc_service = $prevarisc_service;
        $this->piece_service     = $piece_service;
        parent::__construct();
    }

    /**
     * Upload les pièces jointes en attente d'un dossier vers Syncplicity.
     *
     * Le nom de fichier sur Syncplicity est construit comme "{NOM}_{ID}.ext" afin de
     * garantir un data_file_id unique par pièce jointe et d'éviter les collisions quand
     * plusieurs consultations partagent le même nom de fichier original.
     *
     * @param int $type_document Nomenclature TYPE_DOCUMENT Plat'AU (ex. 9 = avis, 47 = PEC)
     *
     * @return array{pieces: list<array<string, mixed>>, pieces_to_export: list<array<string, mixed>>}
     */
    protected function uploaderPiecesJointes(int $dossier_id, int $type_document, OutputInterface $output) : array
    {
        $pieces           = [];
        $pieces_to_export = [];

        if (!$this->piece_service->getSyncplicity()) {
            return compact('pieces', 'pieces_to_export');
        }

        $pieces_to_export = $this->prevarisc_service->recupererPiecesAvecStatut($dossier_id, 'to_be_exported');

        foreach ($pieces_to_export as $piece_jointe) {
            $filename             = $piece_jointe['NOM_PIECEJOINTE'].$piece_jointe['EXTENSION_PIECEJOINTE'];
            $syncplicity_filename = $piece_jointe['NOM_PIECEJOINTE'].'_'.$piece_jointe['ID_PIECEJOINTE'].$piece_jointe['EXTENSION_PIECEJOINTE'];
            $contents             = $this->prevarisc_service->recupererFichierPhysique($output, $piece_jointe['ID_PIECEJOINTE'], $piece_jointe['EXTENSION_PIECEJOINTE']);

            if (null === $contents) {
                $output->writeln(\sprintf('Impossible de récupérer le contenu du fichier %s', $filename));
                $this->prevarisc_service->changerStatutPiece($piece_jointe['ID_PIECEJOINTE'], 'on_error');
                $this->prevarisc_service->ajouterMessageErreurPiece($piece_jointe['ID_PIECEJOINTE'], 'Impossible de récupérer le contenu du fichier');

                continue;
            }

            try {
                $pieces[] = $this->piece_service->uploadDocument($syncplicity_filename, $contents, $type_document);
                $this->prevarisc_service->changerStatutPiece($piece_jointe['ID_PIECEJOINTE'], 'awaiting_status');
            } catch (\Exception $e) {
                $this->prevarisc_service->changerStatutPiece($piece_jointe['ID_PIECEJOINTE'], 'on_error');
                $this->prevarisc_service->ajouterMessageErreurPiece($piece_jointe['ID_PIECEJOINTE'], $e->getMessage());
            }
        }

        return compact('pieces', 'pieces_to_export');
    }
}
