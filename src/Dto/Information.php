<?php

namespace App\Dto;

final readonly class Information
{
    public function __construct(private Dossier $dossier)
    {
    }

    public function getDossier() : Dossier
    {
        return $this->dossier;
    }
}
