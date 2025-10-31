<?php

namespace App\Dto;

class NomGenre {
    private int $idNom;

    public function __construct(int $idNom) {
        $this->idNom = $idNom;
    }

    public function getIdNom() : int {
        return $this->idNom;
    }
}