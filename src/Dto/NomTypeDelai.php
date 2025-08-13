<?php

namespace App\Dto;

class NomTypeDelai
{
    private ?string $libNom;

    public function __construct(?string $libNom)
    {
        $this->libNom = $libNom;
    }

    public function getLibNom() : ?string
    {
        return $this->libNom;
    }
}
