<?php

namespace App\Tests\Service;

use App\Service\DateParser;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class DateParserTest extends TestCase
{
    private DateParser $date_parser;

    protected function setUp() : void
    {
        $this->date_parser = new DateParser();
    }

    public function testRetourneNullSiDateNulle() : void
    {
        self::assertNull($this->date_parser->parse('Y-m-d', null));
    }

    #[DataProvider('datesValides')]
    public function testRetourneDateTimeAvecDateValide(string $format, string $date, string $expected_formatted) : void
    {
        $result = $this->date_parser->parse($format, $date);

        self::assertInstanceOf(\DateTime::class, $result);
        self::assertSame($expected_formatted, $result->format($format));
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function datesValides() : array
    {
        return [
            'format Y-m-d valide' => ['Y-m-d', '2024-06-15', '2024-06-15'],
            'format d/m/Y valide' => ['d/m/Y', '15/06/2024', '15/06/2024'],
            'format Y-m-d en 2000' => ['Y-m-d', '2000-01-01', '2000-01-01'],
        ];
    }

    #[DataProvider('datesInvalides')]
    public function testRetourneNullSiDateInvalide(string $format, string $date) : void
    {
        self::assertNull($this->date_parser->parse($format, $date));
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function datesInvalides() : array
    {
        return [
            'chaîne aléatoire' => ['Y-m-d', 'not-a-date'],
            'format non correspondant' => ['Y-m-d', '15/06/2024'],
            'chaîne vide' => ['Y-m-d', ''],
        ];
    }
}
