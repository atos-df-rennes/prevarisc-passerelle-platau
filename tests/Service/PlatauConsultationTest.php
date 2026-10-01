<?php

namespace App\Tests\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use App\Service\PlatauAbstract;
use PHPUnit\Framework\TestCase;
use App\Service\PlatauConsultation;
use GuzzleHttp\Handler\MockHandler;

final class PlatauConsultationTest extends TestCase
{
    public function testEnvoiPecNeModifiePasLaDateFournieEtCalculeLaDateLimite() : void
    {
        $date_envoi = new \DateTimeImmutable('2025-11-03');
        $options    = $this->sendPec($date_envoi);
        $pec_metier = $options[0]['consultations'][0]['pecMetier'];

        self::assertSame('2025-11-03', $pec_metier['dtPecMetier']);
        self::assertSame('2026-05-03', $pec_metier['dtLimiteReponse']);
        self::assertSame('2025-11-03', $date_envoi->format('Y-m-d'));
    }

    public function testEnvoiPecUtiliseUnIntervalleExplicite() : void
    {
        $options    = $this->sendPec(new \DateTimeImmutable('2025-11-03'), new \DateInterval('P15D'));
        $pec_metier = $options[0]['consultations'][0]['pecMetier'];

        self::assertSame('2025-11-03', $pec_metier['dtPecMetier']);
        self::assertSame('2025-11-18', $pec_metier['dtLimiteReponse']);
    }

    public function testVersementAvisUtiliseLaDateFournie() : void
    {
        $options = $this->sendAvis(new \DateTimeImmutable('2025-11-03'));

        self::assertSame('2025-11-03', $options[0]['avis'][0]['dtAvis']);
    }

    private function sendPec(\DateTimeImmutable $date_envoi, ?\DateInterval $intervalle = null) : array
    {
        $consultation_service = $this->createConsultationService();
        $history              = [];
        $this->installMockHttpClient($consultation_service, $history);
        $consultation_service->envoiPEC('9WD-44J-VD0', true, $intervalle, null, [], $date_envoi);

        return $this->getSubmittedOptions($history, 'pecMetier/consultations');
    }

    private function sendAvis(\DateTimeImmutable $date_envoi) : array
    {
        $consultation_service = $this->createConsultationService();
        $history              = [];
        $this->installMockHttpClient($consultation_service, $history);
        $consultation_service->versementAvis('9WD-44J-VD0', 1, [], [], $date_envoi);

        return $this->getSubmittedOptions($history, 'avis');
    }

    private function createConsultationService() : PlatauConsultation
    {
        $reflection = new \ReflectionClass(PlatauConsultation::class);

        return $reflection->newInstanceWithoutConstructor();
    }

    private function installMockHttpClient(PlatauConsultation $consultation_service, array &$history) : void
    {
        $fixture             = json_decode((string) file_get_contents(__DIR__.'/../fixtures/demandeurs.json'), true, 512, \JSON_THROW_ON_ERROR);
        $pagination_response = json_encode([
            'nombrePages' => 1,
            'resultats' => [$fixture],
        ], \JSON_THROW_ON_ERROR);
        $responses = [];

        for ($index = 0; $index < 10; ++$index) {
            $responses[] = new Response(200, [], $pagination_response);
        }

        $responses[] = new Response(200, [], '{}');

        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($history));

        $http_client = new Client([
            'base_uri' => 'https://platau.test/',
            'handler' => $handler,
        ]);

        $http_client_property = new \ReflectionProperty(PlatauAbstract::class, 'http_client');
        $http_client_property->setValue($consultation_service, $http_client);

        $config_property = new \ReflectionProperty(PlatauAbstract::class, 'config');
        $config_property->setValue($consultation_service, [
            'PLATAU_ID_ACTEUR_APPELANT' => 'test',
        ]);
    }

    private function getSubmittedOptions(array $history, string $uri) : array
    {
        foreach ($history as $transaction) {
            if ('/'.$uri === parse_url((string) $transaction['request']->getUri(), \PHP_URL_PATH)) {
                return json_decode((string) $transaction['request']->getBody(), true, 512, \JSON_THROW_ON_ERROR);
            }
        }

        self::fail("La requête $uri n'a pas été envoyée.");
    }
}
