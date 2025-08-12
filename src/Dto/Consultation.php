<?php

namespace App\Dto;

class Consultation {
  private string $idConsultation;

  private ?int $delaiDeReponse;

  private NomTypeDelai $nomTypeDelai;

  private int $noVersion;

  private NomEtatConsultation $nomEtatConsultation;

  private string $idServiceConsultant;

  private ?string $dtEmission;

  private ?string $dtConsultation;

  private ?string $txObjetDeLaConsultation;

  private NomTypeConsultation  $nomTypeConsultation;

  public function __construct(
    string $idConsultation,
    ?int $delaiDeReponse,
    NomTypeDelai $nomTypeDelai,
    int $noVersion,
    NomEtatConsultation $nomEtatConsultation,
    string $idServiceConsultant,
    ?string $dtEmission,
    ?string $dtConsultation,
    ?string $txObjetDeLaConsultation,
    NomTypeConsultation  $nomTypeConsultation
  ) {
    $this->idConsultation = $idConsultation;
    $this->delaiDeReponse = $delaiDeReponse;
    $this->nomTypeDelai = $nomTypeDelai;
    $this->noVersion = $noVersion;
    $this->nomEtatConsultation = $nomEtatConsultation;
    $this->idServiceConsultant = $idServiceConsultant;
    $this->dtEmission = $dtEmission;
    $this->dtConsultation = $dtConsultation;
    $this->txObjetDeLaConsultation = $txObjetDeLaConsultation;
    $this->nomTypeConsultation = $nomTypeConsultation;
  }

  public function getIdConsultation(): string {
    return $this->idConsultation;
  }

  public function getDelaiDeReponse(): ?int {
    return $this->delaiDeReponse;
  }

  public function getNomTypeDelai(): NomTypeDelai {
    return $this->nomTypeDelai;
  }

  public function getNoVersion(): int {
    return $this->noVersion;
  }

  public function getNomEtatConsultation(): NomEtatConsultation {
    return $this->nomEtatConsultation;
  }

  public function getIdServiceConsultant(): string {
    return $this->idServiceConsultant;
  }

  public function getDtEmission(): ?string {
    return $this->dtEmission;
  }

  public function getDtConsultation(): ?string {
    return $this->dtConsultation;
  }

  public function getTxObjetDeLaConsultation(): ?string {
    return $this->txObjetDeLaConsultation;
  }

  public function getNomTypeConsultation(): NomTypeConsultation {
    return $this->nomTypeConsultation;
  }
}