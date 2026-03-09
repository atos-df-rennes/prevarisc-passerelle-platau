<?php

namespace App\Service;

final class DateParser
{
    /**
     * Crée un objet DateTime à partir d'une chaîne et d'un format.
     * Retourne null si la date est nulle ou si le parsing échoue,
     * contrairement à DateTime::createFromFormat qui peut retourner false.
     */
    public function parse(string $format, ?string $date): ?\DateTime
    {
        if (null === $date) {
            return null;
        }

        $result = \DateTime::createFromFormat($format, $date);

        return false === $result ? null : $result;
    }
}
