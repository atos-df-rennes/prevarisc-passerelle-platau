<?php

namespace App\Dto;

class Personne
{
    /** @var null|string[] */
    private ?array $prenoms;

    /** @var null|string[] */
    private ?array $noms;

    private ?string $libDenomination;

    private ?string $libRaisonSociale;

    /** @var Role[] */
    private array $roles;

    /**
     * @param null|string[] $prenoms
     * @param null|string[] $noms
     * @param Role[]   $roles
     */
    public function __construct(
        ?array $prenoms,
        ?array $noms,
        ?string $libDenomination,
        ?string $libRaisonSociale,
        array $roles = [],
    ) {
        $this->prenoms  = $prenoms;
        $this->noms     = $noms;
        $this->libDenomination = $libDenomination;
        $this->libRaisonSociale = $libRaisonSociale;
        $this->roles    = $roles;
    }

    /**
     * @return null|string[]
     */
    public function getPrenoms() : ?array
    {
        return $this->prenoms;
    }

    /**
     * @return null|string[]
     */
    public function getNoms() :? array
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
     * @return Role[]
     */
    public function getRoles() : array
    {
        return $this->roles;
    }
}
