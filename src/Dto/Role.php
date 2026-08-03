<?php

namespace App\Dto;

class Role
{
    public function __construct(private readonly NomRole $nomRole)
    {
    }

    public function getNomRole() : NomRole
    {
        return $this->nomRole;
    }
}
