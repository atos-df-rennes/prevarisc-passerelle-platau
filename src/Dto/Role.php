<?php

namespace App\Dto;

class Role
{
    private NomRole $nomRole;

    public function __construct(NomRole $nomRole)
    {
        $this->nomRole = $nomRole;
    }

    public function getNomRole() : NomRole
    {
        return $this->nomRole;
    }
}
