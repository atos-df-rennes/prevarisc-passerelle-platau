<?php

namespace App\Dto;

final class NomTypeConsultation
{
    public function __construct(private readonly ?string $libNom)
    {
    }

    public function getLibNom() : ?string
    {
        return $this->libNom;
    }
}
