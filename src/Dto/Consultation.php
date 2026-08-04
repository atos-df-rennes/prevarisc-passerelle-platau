<?php

namespace App\Dto;

final readonly class Consultation
{
    public function __construct(
        private string $idConsultation,
        private ?int $delaiDeReponse,
        private NomTypeDelai $nomTypeDelai,
        private int $noVersion,
        private NomEtatConsultation $nomEtatConsultation,
        private ?string $idServiceConsultant,
        private ?string $dtEmission,
        private ?string $dtConsultation,
        private ?string $txObjetDeLaConsultation,
        private NomTypeConsultation $nomTypeConsultation,
    ) {
    }

    public function getIdConsultation() : string
    {
        return $this->idConsultation;
    }

    public function getDelaiDeReponse() : ?int
    {
        return $this->delaiDeReponse;
    }

    public function getNomTypeDelai() : NomTypeDelai
    {
        return $this->nomTypeDelai;
    }

    public function getNoVersion() : int
    {
        return $this->noVersion;
    }

    public function getNomEtatConsultation() : NomEtatConsultation
    {
        return $this->nomEtatConsultation;
    }

    public function getIdServiceConsultant() : ?string
    {
        return $this->idServiceConsultant;
    }

    public function getDtEmission() : ?string
    {
        return $this->dtEmission;
    }

    public function getDtConsultation() : ?string
    {
        return $this->dtConsultation;
    }

    public function getTxObjetDeLaConsultation() : ?string
    {
        return $this->txObjetDeLaConsultation;
    }

    public function getNomTypeConsultation() : NomTypeConsultation
    {
        return $this->nomTypeConsultation;
    }
}
