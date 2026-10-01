<?php

namespace App\ValueObjects;

use App\Service\CalculDateLimiteReponse;

final class DateReponse
{
    private ?\DateTimeImmutable $date = null;

    public function __construct(?string $dateEmission, ?int $delaiDeReponse, ?string $type_date_limite_reponse)
    {
        if (null !== $dateEmission && null !== $delaiDeReponse) {
            $calcul_date_limite_reponse   = new CalculDateLimiteReponse();
            $date_limite_reponse_interval = $calcul_date_limite_reponse->intervalle($delaiDeReponse, $type_date_limite_reponse);
            $date_depart                  = \DateTimeImmutable::createFromFormat('Y-m-d', $dateEmission);

            if (null !== $date_limite_reponse_interval && false !== $date_depart) {
                $this->date = $calcul_date_limite_reponse->dateLimiteReponse($date_depart, $date_limite_reponse_interval);
            }
        }
    }

    public function date() : ?string
    {
        return $this->date instanceof \DateTime ? $this->date->format('Y-m-d') : null;
    }
}
