<?php

namespace App\Dto;

class Consultation
{
    public function __construct(
        private readonly string $idConsultation,
        private readonly ?int $delaiDeReponse,
        private readonly NomTypeDelai $nomTypeDelai,
        private readonly int $noVersion,
        private readonly NomEtatConsultation $nomEtatConsultation,
        private readonly ?string $idServiceConsultant,
        private readonly ?string $dtEmission,
        private readonly ?string $dtConsultation,
        private readonly ?string $txObjetDeLaConsultation,
        private readonly NomTypeConsultation $nomTypeConsultation
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
