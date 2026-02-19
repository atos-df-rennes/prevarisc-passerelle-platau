<?php

namespace App\Service;

final class PlatauNotification extends PlatauAbstract
{
    /**
     * Recherche de plusieurs notifications.
     */
    public function rechercheNotifications(array $params = []) : array
    {
        // On recherche la consultation en fonction des critères de recherche
        $response = $this->request('get', 'notifications', ['query' => $params]);

        // On vient récupérer les notifications qui nous interesse dans la réponse des résultats de recherche
        $notifications = json_decode($response->getBody()->__toString(), true, 512, \JSON_THROW_ON_ERROR);

        \assert(\is_array($notifications));

        // Les notifications se trouvent normalement sous la clé "notifications" du tableau renvoyé par l'API Plat'AU
        if (!\array_key_exists('notifications', $notifications)) {
            throw new \Exception('Un problème a eu lieu dans la récupération des résultats de recherche de notifications : clé notifications introuvable');
        }

        // Le résultat de la recherche doit donner un tableau, sinon, il y a un problème quelque part ...
        if (!\is_array($notifications['notifications'])) {
            throw new \Exception('Un problème a eu lieu dans la récupération des résultats de recherche de notifications : le résultat est incorrect');
        }

        $set = $notifications['notifications'];

        return $set;
    }

    /**
     * Extrait le code d'erreur du message d'erreur si possible.
     * Le message doit contenir "Code <numero> ou code <numero>".
     *
     * Renvoie null sinon.
     */
    public static function extractErrorCodeFromErrorMessage(string $error_message) : ?int
    {
        if (preg_match('/code[:\s]*(\d+)/i', $error_message, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Identifie l'objet métier concerné par le renvoi de la pièce associée à la consultation.
     *
     * Un objet métier est considéré identifié si son statut est terminé ou en erreur car cela indique
     * qu'un envoi a déjà été effectué et que le renvoi porte donc nécessairement sur cet objet métier.
     *
     * En cas d'impossibilité d'identification, renvoie null.
     */
    public static function identifierObjetMetier(array $consultation_associee) : ?string
    {
        // On regarde en priorité si un avis a été envoyé.
        if ('treated' === $consultation_associee['STATUT_AVIS'] || 'in_error' === $consultation_associee['STATUT_AVIS']) {
            return 'AVIS';
        }

        // Sinon la pec.
        if ('taken_into_account' === $consultation_associee['STATUT_PEC'] || 'in_error' === $consultation_associee['STATUT_PEC']) {
            return 'PEC';
        }

        return null;
    }
}
