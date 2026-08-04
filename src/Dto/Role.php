<?php

namespace App\Dto;

final class Role
{
    public function __construct(private readonly NomRole $nomRole)
    {
    }

    public function getNomRole() : NomRole
    {
        return $this->nomRole;
    }
}
