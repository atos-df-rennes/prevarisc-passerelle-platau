<?php

namespace App\Dto;

class Dossier
{
    private ?string $idDossier;

    private string $idServiceInstructeur;

    private int $noVersion;

    private ?string $txDescriptifGlobal;

    private ?string $noLocal;

    private ?string $suffixeNoLocal;

    private NomTypeDossier $nomTypeDossier;

    /** @var Consultation[] */
    private array $consultations;

    /** @var Personne[] */
    private array $personnes;

    /**
     * @param Consultation[] $consultations
     * @param Personne[] $personnes
     */
    public function __construct(
        ?string $idDossier,
        string $idServiceInstructeur,
        int $noVersion,
        ?string $txDescriptifGlobal,
        ?string $noLocal,
        ?string $suffixeNoLocal,
        NomTypeDossier $nomTypeDossier,
        array $consultations,
        array $personnes = [],
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

    public function getIdServiceInstructeur() : string
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
     * @return Personne[]
     */
    public function getDemandeurs() : array
    {
        $demandeurs = array_filter($this->personnes, function (Personne $personne) {
            $hasRolePetitionnaire = array_filter($personne->getRoles(), function (Role $role) {
                return $role->getNomRole()->getIdNom() === 1;
            });

            return [] !== $hasRolePetitionnaire;
        });

        return $demandeurs;
    }

    /**
     * @param ?\App\Dto\Personne[] $demandeurs
     */
    public function getDemandeursAsString(?array $demandeurs = null): string
    {
        if (null === $demandeurs) {
            $demandeurs = $this->getDemandeurs();
        }

        $demandeurs_names = array_map(function (Personne $personne) {
            $prenoms = array_map('trim', $personne->getPrenoms());
            $noms = array_map('trim', $personne->getNoms());

            return implode(' ', $noms) . ' ' . implode(' ', $prenoms);
        }, $demandeurs);

        return implode(' / ', $demandeurs_names);
    }
}
