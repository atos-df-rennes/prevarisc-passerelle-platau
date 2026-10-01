<?php

namespace App\Service;

final class DateParser
{
    /**
     * Crée un objet DateTimeImmutable à partir d'une chaîne et d'un format.
     * Retourne null si la date est nulle ou si le parsing échoue,
     * contrairement à DateTimeImmutable::createFromFormat qui peut retourner false.
     */
    public function parse(string $format, ?string $date) : ?\DateTimeImmutable
    {
        if (null === $date) {
            return null;
        }

        $result = \DateTimeImmutable::createFromFormat($format, $date);

        return false === $result ? null : $result;
    }
}
