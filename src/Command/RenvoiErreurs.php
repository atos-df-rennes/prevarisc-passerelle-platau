<?php

namespace App\Command;

use App\Service\Prevarisc;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RenvoiErreurs extends Command
{
    private Prevarisc $prevarisc_service;

    public function __construct(Prevarisc $prevarisc_service)
    {
        parent::__construct();
        $this->prevarisc_service = $prevarisc_service;
    }

    protected function configure()
    {
        $this->setName('renvoi-erreurs')
            ->setDescription('Renvoie les pièces et consultations suite à une erreur (principalement pour code erreur 9 sur pièce).')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Chemin vers le fichier de configuration');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $output->writeln(\sprintf('[%s] Recherche des éléments à renvoyer.', (new \DateTime())->format('d-m-Y H:i:s')));

        $pecs_a_renvoyer = $this->prevarisc_service->recupererPecsARenvoyer();
        $avis_a_renvoyer = $this->prevarisc_service->recupererDossiersARenvoyer();

        $output->writeln(\sprintf('%d prise(s) en compte métier à renvoyer.', \count($pecs_a_renvoyer)));
        $output->writeln(\sprintf('%d avis à renvoyer.', \count($avis_a_renvoyer)));

        foreach ($pecs_a_renvoyer as $consultation_id_pec) {
            $output->writeln(\sprintf('Renvoi de la prise en compte métier pour la consultation %s.', $consultation_id_pec));

            try {
                $this->exporterPec($consultation_id_pec, $input, $output);

                $output->writeln('Prise en compte métier renvoyée avec succès.');
            } catch (\RuntimeException $runtimeException) {
                $output->writeln(\sprintf('Erreur lors du renvoi : %s', $runtimeException->getMessage()));
            }
        }

        foreach ($avis_a_renvoyer as $consultation_id_avis) {
            $output->writeln(\sprintf("Renvoi de l'avis pour la consultation %s.", $consultation_id_avis));

            try {
                $this->exporterAvis($consultation_id_avis, $input, $output);

                $output->writeln('Avis renvoyé avec succès.');
            } catch (\RuntimeException $runtimeException) {
                $output->writeln(\sprintf('Erreur lors du renvoi : %s', $runtimeException->getMessage()));
            }
        }

        return Command::SUCCESS;
    }

    private function exporterPec(string $consultation_id, InputInterface $input, OutputInterface $output)
    {
        $config_path = $input->getOption('config');

        $exportPecInput = new ArrayInput([
            'command' => 'export-pec',
            '--consultation-id' => $consultation_id,
            '--config' => $config_path,
        ]);
        $exportPecInput->setInteractive(false);

        $this->getApplication()->doRun($exportPecInput, $output);
    }

    private function exporterAvis(string $consultation_id, InputInterface $input, OutputInterface $output)
    {
        $config_path = $input->getOption('config');

        $exportAvisInput = new ArrayInput([
            'command' => 'export-avis',
            '--consultation-id' => $consultation_id,
            '--config' => $config_path,
        ]);
        $exportAvisInput->setInteractive(false);

        $this->getApplication()->doRun($exportAvisInput, $output);
    }
}
