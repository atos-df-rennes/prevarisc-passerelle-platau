<?php

namespace App\Tests\ValueObjects;

use App\ValueObjects\DateReponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateReponseTest extends TestCase
{
    #[DataProvider('datesReponseProvider')]
    public function testCalculeLaDateDeReponse(?string $date_emission, ?int $delai, ?string $type_delai, ?string $expected_date) : void
    {
        $date_reponse = new DateReponse($date_emission, $delai, $type_delai);

        self::assertSame($expected_date, $date_reponse->date());
    }

    public static function datesReponseProvider() : array
    {
        return [
            'jours calendaires' => ['2025-11-03', 15, 'Jours calendaires', '2025-11-18'],
            'mois' => ['2025-11-03', 6, 'Mois', '2026-05-03'],
            'type inconnu' => ['2025-11-03', 6, 'Années', null],
            'date nulle' => [null, 6, 'Mois', null],
            'délai nul' => ['2025-11-03', null, 'Mois', null],
            'date invalide' => ['date invalide', 6, 'Mois', null],
        ];
    }
}
