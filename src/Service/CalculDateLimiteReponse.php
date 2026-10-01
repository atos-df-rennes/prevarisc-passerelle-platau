<?php

namespace App\Service;

final class CalculDateLimiteReponse
{
    public function intervalle(?int $delai, ?string $type_delai) : ?\DateInterval
    {
        if (null === $delai) {
            return null;
        }

        return match ($type_delai) {
            'Jours calendaires' => new \DateInterval("P{$delai}D"),
            'Mois' => new \DateInterval("P{$delai}M"),
            default => null,
        };
    }

    public function dateLimiteReponse(\DateTimeImmutable $date_depart, \DateInterval $intervalle) : \DateTimeImmutable
    {
        return $date_depart->add($intervalle);
    }
}
