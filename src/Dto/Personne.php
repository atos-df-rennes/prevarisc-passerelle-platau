<?php

namespace App\Dto;

class Personne
{
    /**
     * @param string[]|null $prenoms
     * @param string[]|null $noms
     * @param Role[]|null   $roles
     */
    public function __construct(
        private readonly ?array $prenoms,
        private readonly ?array $noms,
        private readonly ?string $libDenomination,
        private readonly ?string $libRaisonSociale,
        private readonly ?array $roles
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
