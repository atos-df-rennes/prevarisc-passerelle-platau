<?php

namespace App\Dto;

class Personne
{
    /** @var string[]|null */
    private ?array $prenoms;

    /** @var string[]|null */
    private ?array $noms;

    private ?string $libDenomination;

    private ?string $libRaisonSociale;

    /** @var Role[]|null */
    private ?array $roles;

    /**
     * @param string[]|null $prenoms
     * @param string[]|null $noms
     * @param Role[]|null   $roles
     */
    public function __construct(
        ?array $prenoms,
        ?array $noms,
        ?string $libDenomination,
        ?string $libRaisonSociale,
        ?array $roles,
    ) {
        $this->prenoms          = $prenoms;
        $this->noms             = $noms;
        $this->libDenomination  = $libDenomination;
        $this->libRaisonSociale = $libRaisonSociale;
        $this->roles            = $roles;
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
