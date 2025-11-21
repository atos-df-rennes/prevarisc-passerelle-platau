<?php

namespace App\Dto;

class Dossier
{
    private const ROLE_PETITIONNAIRE = 1;

    private ?string $idDossier;

    private ?string $idServiceInstructeur;

    private int $noVersion;

    private ?string $txDescriptifGlobal;

    private ?string $noLocal;

    private ?string $suffixeNoLocal;

    private NomTypeDossier $nomTypeDossier;

    /** @var Consultation[] */
    private array $consultations;

    /** @var Personne[]|null */
    private ?array $personnes;

    /**
     * @param Consultation[]  $consultations
     * @param Personne[]|null $personnes
     */
    public function __construct(
        ?string $idDossier,
        ?string $idServiceInstructeur,
        int $noVersion,
        ?string $txDescriptifGlobal,
        ?string $noLocal,
        ?string $suffixeNoLocal,
        NomTypeDossier $nomTypeDossier,
        array $consultations,
        ?array $personnes,
    ) {
        $this->idDossier            = $idDossier;
        $this->idServiceInstructeur = $idServiceInstructeur;
        $this->noVersion            = $noVersion;
        $this->txDescriptifGlobal   = $txDescriptifGlobal;
        $this->noLocal              = $noLocal;
        $this->suffixeNoLocal       = $suffixeNoLocal;
        $this->nomTypeDossier       = $nomTypeDossier;
        $this->consultations        = $consultations;
        $this->personnes            = $personnes;
    }

    public function getIdDossier() : ?string
    {
        return $this->idDossier;
    }

    public function getIdServiceInstructeur() : ?string
    {
        return $this->idServiceInstructeur;
    }

    public function getNoVersion() : int
    {
        return $this->noVersion;
    }

    public function getTxDescriptifGlobal() : ?string
    {
        return $this->txDescriptifGlobal;
    }

    public function getNoLocal() : ?string
    {
        return $this->noLocal;
    }

    public function getSuffixeNoLocal() : ?string
    {
        return $this->suffixeNoLocal;
    }

    public function getNomTypeDossier() : NomTypeDossier
    {
        return $this->nomTypeDossier;
    }

    /**
     * @return Consultation[]
     */
    public function getConsultations() : array
    {
        return $this->consultations;
    }

    public function getConsultation() : Consultation
    {
        $first_key = array_key_first($this->consultations);

        if (null === $first_key) {
            throw new \Exception('Aucune consultation pour ce dossier.');
        }

        return $this->consultations[$first_key];
    }

    /**
     * Retourne les demandeurs du dossier (rôle = pétitionnaire).
     * Si le dossier n'a pas de demandeurs, renvoie un tableau vide.
     * Si le dossier a des demandeurs mais qu'un demandeur n'a pas de rôle, l'exclut de la liste.
     *
     * @return Personne[]
     */
    public function getDemandeurs() : array
    {
        $personnes = $this->personnes ?? [];

        $demandeurs = array_filter($personnes, function (Personne $personne) {
            $roles = $personne->getRoles();

            if (null === $roles) {
                return false;
            }

            $hasRolePetitionnaire = array_filter($roles, function (Role $role) {
                return self::ROLE_PETITIONNAIRE === $role->getNomRole()->getIdNom();
            });

            return [] !== $hasRolePetitionnaire;
        });

        return $demandeurs;
    }

    /**
     * Renvoie les noms des demandeurs du dossier.
     * Si les prénoms et noms ne sont pas indiqués, renvoie la dénomination ou la raison sociale.
     * Le cas échéant, indique que l'information n'est pas connue.
     *
     * @param Personne[]|null $demandeurs
     */
    public function getDemandeursAsString(?array $demandeurs = null) : ?string
    {
        if (null === $demandeurs) {
            $demandeurs = $this->getDemandeurs();
        }

        if ([] === $demandeurs) {
            return null;
        }

        $demandeurs_names = array_map(function (Personne $personne) {
            $prenoms = $personne->getPrenoms();
            $noms    = $personne->getNoms();

            if (null === $prenoms && null === $noms) {
                return $personne->getLibDenomination() ?? $personne->getLibRaisonSociale() ?? 'Inconnu';
            }

            if (null === $prenoms) {
                $prenoms = [];
            }
            if (null === $noms) {
                $noms = [];
            }

            $prenoms = array_map('trim', $prenoms);
            $noms    = array_map('trim', $noms);

            $nom_complet = implode(' ', $noms);
            if ([] !== $noms && [] !== $prenoms) {
                $nom_complet .= ' ';
            }
            $nom_complet .= implode(' ', $prenoms);

            return $nom_complet;
        }, $demandeurs);

        return implode(' / ', $demandeurs_names);
    }
}
