<?php

namespace App\Service;

final class CalculDateLimiteReponse
{
    public function intervalle(?int $delai, ?string $type_delai) : ?\DateInterval
    {
        if (null === $delai) {
            return null;
        }

        switch ($type_delai) {
            case 'Jours calendaires':
                return new \DateInterval("P{$delai}D");
            case 'Mois':
                return new \DateInterval("P{$delai}M");
            default:
                return null;
        }
    }

    public function dateLimiteReponse(\DateTimeImmutable $date_depart, \DateInterval $intervalle) : \DateTimeImmutable
    {
        return $date_depart->add($intervalle);
    }
}
