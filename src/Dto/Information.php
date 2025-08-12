<?php

namespace App\Dto;

class Information
{
    private Dossier $dossier;

    public function __construct(Dossier $dossier)
    {
        $this->dossier = $dossier;
    }

    public function getDossier() : Dossier
    {
        return $this->dossier;
    }
}
