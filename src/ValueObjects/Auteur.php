<?php

namespace App\ValueObjects;

final class Auteur
{
    private readonly ?string $telephoneAuteur;

    public function __construct(
        private readonly ?string $prenomAuteur,
        private readonly ?string $nomAuteur,
        private readonly ?string $emailAuteur,
        ?string $telephone_fixe,
        ?string $telephone_portable,
    ) {
        $this->telephoneAuteur = '' !== $telephone_fixe ? $telephone_fixe : $telephone_portable;
    }

    public function prenom() : ?string
    {
        return $this->prenomAuteur;
    }

    public function nom() : ?string
    {
        return $this->nomAuteur;
    }

    public function email() : ?string
    {
        return $this->emailAuteur;
    }

    public function telephone() : ?string
    {
        return $this->telephoneAuteur;
    }
}
