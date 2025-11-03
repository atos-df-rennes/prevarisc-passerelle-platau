<?php

namespace App\Dto;

class Personne
{
    /** @var string[] */
    private array $prenoms;

    /** @var string[] */
    private array $noms;

    private NomGenre $nomGenre;

    /** @var Role[] */
    private array $roles;

    /**
     * @param string[] $prenoms
     * @param string[] $noms
     * @param Role[]   $roles
     */
    public function __construct(
        array $prenoms,
        array $noms,
        NomGenre $nomGenre,
        array $roles = [],
    ) {
        $this->prenoms  = $prenoms;
        $this->noms     = $noms;
        $this->nomGenre = $nomGenre;
        $this->roles    = $roles;
    }

    /**
     * @return string[]
     */
    public function getPrenoms() : array
    {
        return $this->prenoms;
    }

    /**
     * @return string[]
     */
    public function getNoms() : array
    {
        return $this->noms;
    }

    public function getNomGenre() : NomGenre
    {
        return $this->nomGenre;
    }

    /**
     * @return Role[]
     */
    public function getRoles() : array
    {
        return $this->roles;
    }
}
