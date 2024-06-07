<?php

namespace App\ValueObjects;

use DateTime;

class DateReponse
{
    private ?DateTime $date;

    public function __construct(?string $dateEmission, ?string $delaiDeReponse, ?string $type_date_limite_reponse)
    {
        switch ($type_date_limite_reponse) {
            case 'Jours calendaires': $date_limite_reponse_interval = new \DateInterval("P{$delaiDeReponse}D");
                break;
            case 'Mois': $date_limite_reponse_interval              = new \DateInterval("P{$delaiDeReponse}M");
                break;
            default: throw new \Exception('Type de la date de réponse attendue inconnu : '.$type_date_limite_reponse);
        } 
        $dateEmission          =  \DateTime::createFromFormat('Y-m-d', $dateEmission)  ?? (new \DateTime()); 
        $this->date            = $dateEmission ->add($date_limite_reponse_interval);
    }
    public function date() : ?string
    {
        return $this->date ? $this->date->format('Y-m-d') : null;
    }
    


}