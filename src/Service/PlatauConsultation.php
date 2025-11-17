<?php

namespace App\Service;

use App\Dto\Information;
use App\ValueObjects\Auteur;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;

final class PlatauConsultation extends PlatauAbstract
{
    /**
     * Recherche de plusieurs consultations.
     *
     * @return Information[]
     */
    public function rechercheConsultations(array $params = [], string $order_by = 'DT_LIMITE_DE_REPONSE', string $sort = 'DESC') : array
    {
        // On recherche la consultation en fonction des critères de recherche
        $paginator = $this->pagination('post', 'consultations/recherche', [
            'json' => [
                'criteresSurConsultations' => $params,
            ],
            'query' => [
                'colonneTri' => $order_by,
                'sensTri' => $sort,
            ],
        ]);

        $consultations = [];

        $normalizers = [new ArrayDenormalizer(), new ObjectNormalizer(null, null, null, new PhpDocExtractor())];
        $serializer  = new Serializer($normalizers);

        foreach ($paginator->autoPagingIterator() as $information) {
            \assert(\is_array($information));
            $consultations[] = $serializer->denormalize($information, Information::class);
        }

        return $consultations;
    }

    /**
     * Recherche de plusieurs consultations.
     */
    public function rechercheConsultationsAsArray(array $params = [], string $order_by = 'DT_LIMITE_DE_REPONSE', string $sort = 'DESC') : array
    {
        // On recherche la consultation en fonction des critères de recherche
        $paginator = $this->pagination('post', 'consultations/recherche', [
            'json' => [
                'criteresSurConsultations' => $params,
            ],
            'query' => [
                'colonneTri' => $order_by,
                'sensTri' => $sort,
            ],
        ]);

        $consultations = [];

        foreach ($paginator->autoPagingIterator() as $information) {
            \assert(\is_array($information));
            $consultations[] = $information;
        }

        return $consultations;
    }

    /**
     * Recherche de plusieurs consultations avec pour critères des éléments du dossier.
     */
    public function rechercheConsultationsAvecCriteresDossier(array $params = [], string $order_by = 'DT_LIMITE_DE_REPONSE', string $sort = 'DESC') : array
    {
        // On recherche la consultation en fonction des critères de recherche
        $paginator = $this->pagination('post', 'consultations/recherche', [
            'json' => [
                'criteresSurDossiers' => $params,
            ],
            'query' => [
                'colonneTri' => $order_by,
                'sensTri' => $sort,
            ],
        ]);

        $consultations = [];

        /** @var array $result */
        foreach ($paginator->autoPagingIterator() as $result) {
            /** @var array $consultation */
            foreach ($result['dossier']['consultations'] as $consultation) {
                $consultations[] = $consultation;
            }
        }

        return $consultations;
    }

    /**
     * Récupération des informations d'une consultation avec les informations du dossier.
     *
     * @return Information|array
     */
    public function getConsultation(string $consultation_id, array $params = [], bool $as_array = false)
    {
        // On recherche la consultation demandée
        if ($as_array) {
            $consultations = $this->rechercheConsultationsAsArray(['idConsultation' => $consultation_id] + $params);
        } else {
            $consultations = $this->rechercheConsultations(['idConsultation' => $consultation_id] + $params);
        }

        // Si la liste des consultations est vide, alors on lève une erreur (la recherche n'a rien donné)
        if (empty($consultations)) {
            throw new \Exception("la consultation $consultation_id est introuvable selon les critères de recherche");
        }

        // On vient récupérer la consultation qui nous interesse dans le tableau des résultats
        $consultation = array_shift($consultations);

        if ($as_array) {
            \assert(\is_array($consultation));
            \assert(1 === \count($consultation['dossier']['consultations']));
        } else {
            \assert($consultation instanceof Information);
            \assert(1 === \count($consultation->getDossier()->getConsultations()));
        }

        return $consultation;
    }

    /**
     * Récupération des pièces d'un dossier.
     */
    public function getPieces(string $dossier_id) : array
    {
        // On recherche l'ensemble des pièces liées au dossier
        $response = $this->request('get', 'dossiers/'.$dossier_id.'/pieces');

        // On vient récupérer les pièces qui nous interesse dans la réponse des résultats de recherche
        $pieces = json_decode($response->getBody()->__toString(), true, 512, \JSON_THROW_ON_ERROR);

        \assert(\is_array($pieces));

        return $pieces;
    }

    /**
     * Envoi d'une PEC sur une consultation.
     */
    public function envoiPEC(string $consultation_id, bool $est_positive = true, ?\DateInterval $date_limite_reponse_interval = null, ?string $observations = null, array $documents = [], ?\DateTime $date_envoi = null, ?Auteur $auteur = null) : ResponseInterface
    {
        // On recherche dans Plat'AU les détails de la consultation liée à la PEC
        /** @var Information $information */
        $information  = $this->getConsultation($consultation_id);
        $dossier      = $information->getDossier();
        $consultation = $dossier->getConsultation();

        // Définition de la DLR à envoyer
        // Correspond à la date d'instruction donnée dans la consultation si aucune date limite est donnée
        if (null === $date_limite_reponse_interval) {
            $delai_reponse            = (string) $consultation->getDelaiDeReponse();
            $type_date_limite_reponse = $consultation->getNomTypeDelai()->getLibNom();
            switch ($type_date_limite_reponse) {
                case 'Jours calendaires': $date_limite_reponse_interval = new \DateInterval("P{$delai_reponse}D");
                    break;
                case 'Mois': $date_limite_reponse_interval              = new \DateInterval("P{$delai_reponse}M");
                    break;
                default: throw new \Exception('Type de la date de réponse attendue inconnu : '.($type_date_limite_reponse ?? 'vide'));
            }
        }

        $date_envoi          = $date_envoi ?? (new \DateTime());
        $date_limite_reponse = $date_envoi->add($date_limite_reponse_interval);

        $pec_metier_options = [
            'dtPecMetier' => $date_envoi->format('Y-m-d'),
            'dtLimiteReponse' => $date_limite_reponse->format('Y-m-d'),
            'idActeurEmetteur' => $this->getConfig()['PLATAU_ID_ACTEUR_APPELANT'],
            'nomStatutPecMetier' => $est_positive ? 1 : 2,
            'txObservations' => (string) $observations,
            'documents' => $documents,
        ];

        if (null !== $auteur) {
            $pec_metier_options += [
                'prenomAuteur' => $auteur->prenom(),
                'nomAuteur' => $auteur->nom(),
                'emailAuteur' => $auteur->email(),
                'telephoneAuteur' => $auteur->telephone(),
            ];
        }

        // Envoie de la PEC dans Plat'AU
        return $this->request('post', 'pecMetier/consultations', [
            'json' => [
                [
                    'consultations' => [
                        [
                            'idConsultation' => $consultation_id,
                            'noVersion' => $consultation->getNoVersion(),
                            'pecMetier' => $pec_metier_options,
                        ],
                    ],
                    'idDossier' => $dossier->getIdDossier(),
                    'noVersion' => $dossier->getNoVersion(),
                ],
            ],
        ]);
    }

    /**
     * Versement d'un avis sur une consultation.
     */
    public function versementAvis(string $consultation_id, int $avis_rendu, array $prescriptions = [], array $documents = [], ?\DateTime $date_envoi = null, ?Auteur $auteur = null) : ResponseInterface
    {
        // On recherche dans Plat'AU les détails de la consultation liée (dans les traitées et versées)
        /** @var Information $information */
        $information = $this->getConsultation($consultation_id, ['nomEtatConsultation' => [3, 6]]);
        $dossier     = $information->getDossier();

        // Création du texte formulant l'avis
        /** @var array<array-key, string> $libelles */
        $libelles    = array_column($prescriptions, 'libelle');
        $description = vsprintf('Avis Prevarisc. Prescriptions données : %s', [
            0 === \count($prescriptions) ? 'RAS' : implode(', ', $libelles),
        ]);

        $date_envoi = $date_envoi ?? (new \DateTime());

        $avis_options = [
            'idConsultation' => $consultation_id,
            'boEstTacite' => false, // Un avis envoyé ne sera jamais tacite, il doit être considéré comme étant un avis "express" dans tous les cas
            'nomNatureAvisRendu' => $avis_rendu, // 1 = favorable, 2 = favorable avec prescriptions, 3 = défavorable, 6 = pas d'avis - à motiver dans la partie Fondement de l'avis
            'nomTypeAvis' => 1, // Avis de type "simple"
            'txAvis' => $description,
            'dtAvis' => $date_envoi->format('Y-m-d'),
            'idActeurAuteur' => $this->getConfig()['PLATAU_ID_ACTEUR_APPELANT'],
            'documents' => $documents,
        ];

        if (null !== $auteur) {
            $avis_options += [
                'prenomAuteur' => $auteur->prenom(),
                'nomAuteur' => $auteur->nom(),
                'emailAuteur' => $auteur->email(),
                'telephoneAuteur' => $auteur->telephone(),
            ];
        }

        // Versement d'un avis
        return $this->request('post', 'avis', [
            'json' => [
                [
                    'avis' => [
                        $avis_options,
                    ],
                    'idDossier' => $dossier->getIdDossier(),
                    'noVersion' => $dossier->getNoVersion(),
                ],
            ],
        ]);
    }
}
