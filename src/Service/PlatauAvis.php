<?php

namespace App\Service;

final class PlatauAvis extends PlatauAbstract
{
    /**
     * Recherche de plusieurs avis.
     */
    public function rechercheAvis(array $params = []) : array
    {
        // On recherche l'avis en fonction des critères de recherche
        $paginator = $this->pagination('post', 'avis/recherche', [
            'json' => [
                'criteresSurConsultations' => $params,
            ],
        ]);

        $avis = [];

        foreach ($paginator->autoPagingIterator() as $avis_pagines) {
            \assert(\is_array($avis_pagines));

            /** @var array $avis_simple */
            foreach ($avis_pagines['dossier']['avis'] as $avis_simple) {
                $avis[] = $avis_simple;
            }
        }

        return $avis;
    }

    /**
     * Récupération d'un avis d'une consultation.
     */
    public function getAvisForConsultation(string $consultation_id, array $params = []) : array
    {
        // On recherche les avis  de la consultation demandée
        $avis = $this->rechercheAvis(['idConsultation' => $consultation_id] + $params);

        // Si la liste des avis est vide, alors on lève une erreur (la recherche n'a rien donné)
        if (empty($avis)) {
            throw new \Exception("l'avis $consultation_id est introuvable selon les critères de recherche");
        }

        // On inverse le tableau pour récupérer l'avis le plus récent
        $last_avis = array_reverse($avis);

        // On vient récupérer l'avis qui nous interesse dans le tableau des résultats
        $avis_simple = array_shift($last_avis);

        \assert(\is_array($avis_simple));

        return $avis_simple;
    }
}
