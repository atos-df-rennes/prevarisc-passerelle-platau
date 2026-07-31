<?php

namespace App\Dto;

class Information
{
    public function __construct(private readonly Dossier $dossier)
    {
    }

    public function getDossier() : Dossier
    {
        return $this->dossier;
    }
}
