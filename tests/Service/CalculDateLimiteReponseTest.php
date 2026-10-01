<?php

namespace App\Tests\Service;

use App\Service\CalculDateLimiteReponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CalculDateLimiteReponseTest extends TestCase
{
    #[DataProvider('intervalleProvider')]
    public function testRetourneIntervalleAttendu(?int $delai, ?string $type_delai, ?string $expected_intervalle) : void
    {
        $intervalle = (new CalculDateLimiteReponse())->intervalle($delai, $type_delai);

        if (null === $expected_intervalle) {
            self::assertNull($intervalle);

            return;
        }

        self::assertInstanceOf(\DateInterval::class, $intervalle);
        self::assertSame($expected_intervalle, $intervalle->format('%rP%yY%mM%dDT%hH%iM%sS'));
    }

    public static function intervalleProvider() : array
    {
        return [
            'jours calendaires' => [15, 'Jours calendaires', 'P0Y0M15DT0H0M0S'],
            'mois' => [6, 'Mois', 'P0Y6M0DT0H0M0S'],
            'type inconnu' => [6, 'Années', null],
            'délai nul' => [null, 'Mois', null],
        ];
    }

    public function testCalculeLaDateLimiteSansModifierLaDateDeDepart() : void
    {
        $calcul      = new CalculDateLimiteReponse();
        $date_depart = new \DateTimeImmutable('2025-11-03');
        $date_limite = $calcul->dateLimiteReponse($date_depart, new \DateInterval('P6M'));

        self::assertSame('2025-11-03', $date_depart->format('Y-m-d'));
        self::assertSame('2026-05-03', $date_limite->format('Y-m-d'));
    }
}
