<?php

namespace App\Tests\Dto;

use App\Dto\Dossier;
use App\Dto\Information;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\SerializerInterface;

final class DossierTest extends TestCase
{
    private SerializerInterface $serializer;

    protected function setUp() : void
    {
        parent::setUp();

        $normalizers = [new ArrayDenormalizer(), new ObjectNormalizer(null, null, null, new PhpDocExtractor())];
        $this->serializer  = new Serializer($normalizers);
    }

    /**
     * @dataProvider demandeurs
     */
    public function testReturnsCorrectDemandeurs(string $fixtureFileName, int $expectedDemandeursCount, ?string $expectedDemandeursNames) : void
    {
        $information  = json_decode(file_get_contents($fixtureFileName));
        $consultation = $this->serializer->denormalize($information, Information::class);

        $dossier    = $consultation->getDossier();
        $demandeurs = $dossier->getDemandeurs();

        $this->assertCount($expectedDemandeursCount, $demandeurs);
        $this->assertSame($expectedDemandeursNames, $dossier->getDemandeursAsString($demandeurs));
    }

    public static function demandeurs(): \Generator
    {
        yield 'demandeurs renseignés avec rôle valide' => [
            __DIR__.'/../fixtures/demandeurs.json',
            2,
            'BROUARD Guillaume / CARABI CARABO Toto Test'
        ];

        yield 'demandeurs non renseignés' => [
            __DIR__.'/../fixtures/demandeurs_vide.json',
            0,
            null
        ];

        yield 'demandeurs renseignés avec rôle non valide' => [
            __DIR__.'/../fixtures/demandeurs_role_invalide.json',
            1,
            'CARABI CARABO Toto Test'
        ];

        yield 'demandeurs sans noms' => [
            __DIR__.'/../fixtures/demandeurs_sans_noms.json',
            2,
            'BROUARD Guillaume / Toto Test'
        ];

        yield 'demandeurs sans prénoms' => [
            __DIR__.'/../fixtures/demandeurs_sans_prenoms.json',
            2,
            'BROUARD Guillaume / CARABI CARABO'
        ];

        yield 'demandeurs sans prénoms noms' => [
            __DIR__.'/../fixtures/demandeurs_sans_prenoms_noms.json',
            2,
            'BROUARD Guillaume / Dénomination TOTO'
        ];

        yield 'demandeurs sans prénoms noms dénomination' => [
            __DIR__.'/../fixtures/demandeurs_sans_prenoms_noms_denomination.json',
            2,
            'BROUARD Guillaume / Raison sociale TOTO'
        ];

        yield 'demandeurs sans infos' => [
            __DIR__.'/../fixtures/demandeurs_sans_infos.json',
            2,
            'BROUARD Guillaume / Inconnu'
        ];
    }
}
