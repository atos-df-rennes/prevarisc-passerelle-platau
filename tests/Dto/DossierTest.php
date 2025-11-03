<?php

namespace App\Tests\Dto;

use App\Dto\Dossier;
use App\Dto\Information;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;

final class DossierTest extends TestCase
{
    private Dossier $dossier;

    /** @var \App\Dto\Personne[] */
    private array $demandeurs;

    protected function setUp() : void
    {
        parent::setUp();

        $normalizers = [new ArrayDenormalizer(), new ObjectNormalizer(null, null, null, new PhpDocExtractor())];
        $serializer  = new Serializer($normalizers);

        $information  = json_decode(file_get_contents(__DIR__.'/../fixtures/demandeurs.json'));
        $consultation = $serializer->denormalize($information, Information::class);

        $this->dossier    = $consultation->getDossier();
        $this->demandeurs = $this->dossier->getDemandeurs();
    }

    public function testReturnsCorrectNumberOfDemandeurs() : void
    {
        $this->assertCount(2, $this->demandeurs);
    }

    public function testReturnsCorrectDemandeursNames() : void
    {
        $this->assertSame('BROUARD Guillaume / CARABI CARABO Toto Test', $this->dossier->getDemandeursAsString($this->demandeurs));
    }
}
