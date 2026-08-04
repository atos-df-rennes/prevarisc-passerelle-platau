<?php

namespace App\Dto;

final class NomRole
{
    public function __construct(private readonly int $idNom)
    {
    }

    public function getIdNom() : int
    {
        return $this->idNom;
    }
}
