<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\PlatauNotification;

final class PlatauNotificationTest extends TestCase
{
    /**
     * @dataProvider errorMessagesProvider
     */
    public function testExtractErrorCodeFromErrorMessage(string $error_message, ?int $expected_code)
    {
        $this->assertSame($expected_code, PlatauNotification::extractErrorCodeFromErrorMessage($error_message));
    }

    public static function errorMessagesProvider() : \Generator
    {
        yield 'erreur générique' => [
            'Erreur inconnue',
            null,
        ];

        yield 'erreur avec code' => [
            'Code 9',
            9,
        ];

        yield 'erreur avec code minuscule' => [
            'code 9',
            9,
        ];

        yield 'erreur avec code et message' => [
            'Code 9 - Erreur inconnue',
            9,
        ];

        yield 'erreur avec message' => [
            'Erreur inconnue - Code 9',
            9,
        ];
    }

    /**
     * @dataProvider objetMetierProvider
     */
    public function testIdentifierObjetMetier(array $informations_consultation, ?string $expected_objet_metier) : void
    {
        $this->assertSame($expected_objet_metier, PlatauNotification::identifierObjetMetier($informations_consultation));
    }

    public static function objetMetierProvider() : \Generator
    {
        yield 'sans information' => [
            [
                'STATUT_PEC' => null,
                'STATUT_AVIS' => null,
            ],
            null,
        ];

        yield 'pec pas encore traitée' => [
            [
                'STATUT_PEC' => 'awaiting',
                'STATUT_AVIS' => null,
            ],
            null,
        ];

        yield 'pec traitée sans avis' => [
            [
                'STATUT_PEC' => 'taken_into_account',
                'STATUT_AVIS' => null,
            ],
            'PEC',
        ];

        yield 'pec en erreur sans avis' => [
            [
                'STATUT_PEC' => 'in_error',
                'STATUT_AVIS' => null,
            ],
            'PEC',
        ];

        yield 'pec traitée avec avis non traité' => [
            [
                'STATUT_PEC' => 'taken_into_account',
                'STATUT_AVIS' => 'in_progress',
            ],
            'PEC',
        ];

        yield 'avis traité avec pec traitée' => [
            [
                'STATUT_PEC' => 'taken_into_account',
                'STATUT_AVIS' => 'treated',
            ],
            'AVIS',
        ];

        yield 'avis en erreur avec pec traitée' => [
            [
                'STATUT_PEC' => 'taken_into_account',
                'STATUT_AVIS' => 'in_error',
            ],
            'AVIS',
        ];
    }
}
