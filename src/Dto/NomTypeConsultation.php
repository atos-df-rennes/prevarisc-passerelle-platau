<?php

namespace App\Dto;

final readonly class NomTypeConsultation
{
    public function __construct(private ?string $libNom)
    {
    }

    public function getLibNom() : ?string
    {
        return $this->libNom;
    }
}
