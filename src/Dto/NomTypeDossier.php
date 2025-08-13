<?php

namespace App\Dto;

class NomTypeDossier
{
    private int $idNom;

    public function __construct(int $idNom)
    {
        $this->idNom = $idNom;
    }

    public function getIdNom() : int
    {
        return $this->idNom;
    }
}
