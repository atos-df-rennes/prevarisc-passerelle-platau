<?php

namespace App\Dto;

class Dossier
{
    private ?string $idDossier;

    private string $idServiceInstructeur;

    private int $noVersion;

    private string $txDescriptifGlobal;

    private ?string $noLocal;

    private ?string $suffixeNoLocal;

    private NomTypeDossier $nomTypeDossier;

    /** @var Consultation[] */
    private array $consultations;

    /** @param Consultation[] $consultations */
    public function __construct(
        ?string $idDossier,
        string $idServiceInstructeur,
        int $noVersion,
        string $txDescriptifGlobal,
        ?string $noLocal,
        ?string $suffixeNoLocal,
        NomTypeDossier $nomTypeDossier,
        array $consultations,
    ) {
        $this->idDossier            = $idDossier;
        $this->idServiceInstructeur = $idServiceInstructeur;
        $this->noVersion            = $noVersion;
        $this->txDescriptifGlobal   = $txDescriptifGlobal;
        $this->noLocal              = $noLocal;
        $this->suffixeNoLocal       = $suffixeNoLocal;
        $this->nomTypeDossier       = $nomTypeDossier;
        $this->consultations        = $consultations;
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

    public function getTxDescriptifGlobal() : string
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
}
