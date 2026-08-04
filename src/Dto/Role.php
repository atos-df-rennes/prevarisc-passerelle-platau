<?php

namespace App\Dto;

final readonly class Role
{
    public function __construct(private NomRole $nomRole)
    {
    }

    public function getNomRole() : NomRole
    {
        return $this->nomRole;
    }
}
