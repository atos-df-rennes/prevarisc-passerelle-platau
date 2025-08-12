<?php

namespace App\Dto;

class NomTypeConsultation {
  private ?string $libNom;

  public function __construct(?string $libNom) {
    $this->libNom = $libNom;
  }

  public function getLibNom(): ?string {
    return $this->libNom;
  }
}