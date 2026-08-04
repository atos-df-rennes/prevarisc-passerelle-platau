<?php

namespace App\Dto;

final readonly class NomTypeDossier
{
    public function __construct(private int $idNom)
    {
    }

    public function getIdNom() : int
    {
        return $this->idNom;
    }
}
