<?php

namespace App\Dto;

class NomTypeDossier
{
    public function __construct(private readonly int $idNom)
    {
    }

    public function getIdNom() : int
    {
        return $this->idNom;
    }
}
