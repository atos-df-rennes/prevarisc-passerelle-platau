<?php

namespace App\ValueObjects;

class DateReponse
{
    private ?\DateTime $date = null;

    public function __construct(?string $dateEmission, ?int $delaiDeReponse, ?string $type_date_limite_reponse)
    {
        if (null !== $dateEmission && null !== $delaiDeReponse) {
            $date_limite_reponse_interval = null;

            switch ($type_date_limite_reponse) {
                case 'Jours calendaires': $date_limite_reponse_interval = new \DateInterval("P{$delaiDeReponse}D");
                    break;
                case 'Mois': $date_limite_reponse_interval              = new \DateInterval("P{$delaiDeReponse}M");
                    break;
                default:
                    break;
            }

            if (null !== $date_limite_reponse_interval) {
                $dateEmission          = \DateTime::createFromFormat('Y-m-d', $dateEmission);
                $this->date            = $dateEmission->add($date_limite_reponse_interval);
            }
        }
    }

    public function date() : ?string
    {
        return $this->date ? $this->date->format('Y-m-d') : null;
    }
}
