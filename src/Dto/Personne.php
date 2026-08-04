<?php

namespace App\Dto;

final readonly class Personne
{
    public function __construct(
        /** @var string[]|null */
        private ?array $prenoms,
        /** @var string[]|null */
        private ?array $noms,
        private ?string $libDenomination,
        private ?string $libRaisonSociale,
        /** @var Role[]|null */
        private ?array $roles,
    ) {
    }

    /**
     * @return string[]|null
     */
    public function getPrenoms() : ?array
    {
        return $this->prenoms;
    }

    /**
     * @return string[]|null
     */
    public function getNoms() : ?array
    {
        return $this->noms;
    }

    public function getLibDenomination() : ?string
    {
        return $this->libDenomination;
    }

    public function getLibRaisonSociale() : ?string
    {
        return $this->libRaisonSociale;
    }

    /**
     * @return Role[]|null
     */
    public function getRoles() : ?array
    {
        return $this->roles;
    }
}
