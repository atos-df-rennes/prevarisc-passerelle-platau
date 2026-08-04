<?php

namespace App\ValueObjects;

final readonly class Auteur
{
    private ?string $telephoneAuteur;

    public function __construct(
        private ?string $prenomAuteur,
        private ?string $nomAuteur,
        private ?string $emailAuteur,
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
