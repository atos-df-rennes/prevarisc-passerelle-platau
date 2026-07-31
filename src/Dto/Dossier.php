<?php

namespace App\Dto;

class Dossier
{
    private const ROLE_PETITIONNAIRE = 1;

    /**
     * @param Consultation[]  $consultations
     * @param Personne[]|null $personnes
     */
    public function __construct(
        private readonly ?string $idDossier,
        private readonly ?string $idServiceInstructeur,
        private readonly int $noVersion,
        private readonly ?string $txDescriptifGlobal,
        private readonly ?string $noLocal,
        private readonly ?string $suffixeNoLocal,
        private readonly NomTypeDossier $nomTypeDossier,
        private array $consultations,
        private readonly ?array $personnes,
    ) {
    }

    public function getIdDossier() : ?string
    {
        return $this->idDossier;
    }

    public function getIdServiceInstructeur() : ?string
    {
        return $this->idServiceInstructeur;
    }

    public function getNoVersion() : int
    {
        return $this->noVersion;
    }

    public function getTxDescriptifGlobal() : ?string
    {
        return $this->txDescriptifGlobal;
    }

    public function getNoLocal() : ?string
    {
        return $this->noLocal;
    }

    public function getSuffixeNoLocal() : ?string
    {
        return $this->suffixeNoLocal;
    }

    public function getNomTypeDossier() : NomTypeDossier
    {
        return $this->nomTypeDossier;
    }

    /**
     * @return Consultation[]
     */
    public function getConsultations() : array
    {
        return $this->consultations;
    }

    public function getConsultation() : Consultation
    {
        $first_key = array_key_first($this->consultations);

        if (null === $first_key) {
            throw new \Exception('Aucune consultation pour ce dossier.');
        }

        return $this->consultations[$first_key];
    }

    /**
     * Retourne les demandeurs du dossier (rôle = pétitionnaire).
     * Si le dossier n'a pas de demandeurs, renvoie un tableau vide.
     * Si le dossier a des demandeurs mais qu'un demandeur n'a pas de rôle, l'exclut de la liste.
     *
     * @return Personne[]
     */
    public function getDemandeurs() : array
    {
        $personnes = $this->personnes ?? [];

        $demandeurs = array_filter($personnes, static function (Personne $personne): bool {
            $roles = $personne->getRoles();

            if (null === $roles) {
                return false;
            }

            $hasRolePetitionnaire = array_filter($roles, static fn (Role $role): bool => self::ROLE_PETITIONNAIRE === $role->getNomRole()->getIdNom());

            return [] !== $hasRolePetitionnaire;
        });

        return $demandeurs;
    }

    /**
     * Renvoie les noms des demandeurs du dossier.
     * Si les prénoms et noms ne sont pas indiqués, renvoie la dénomination ou la raison sociale.
     * Le cas échéant, indique que l'information n'est pas connue.
     *
     * @param Personne[]|null $demandeurs
     */
    public function getDemandeursAsString(?array $demandeurs = null) : ?string
    {
        if (null === $demandeurs) {
            $demandeurs = $this->getDemandeurs();
        }

        if ([] === $demandeurs) {
            return null;
        }

        $demandeurs_names = array_map(static function (Personne $personne): string {
            $prenoms = $personne->getPrenoms();
            $noms    = $personne->getNoms();

            if (null === $prenoms && null === $noms) {
                return $personne->getLibDenomination() ?? $personne->getLibRaisonSociale() ?? 'Inconnu';
            }

            if (null === $prenoms) {
                $prenoms = [];
            }
            if (null === $noms) {
                $noms = [];
            }

            $prenoms = array_map(trim(...), $prenoms);
            $noms    = array_map(trim(...), $noms);

            $nom_complet = implode(' ', $noms);
            if ([] !== $noms && [] !== $prenoms) {
                $nom_complet .= ' ';
            }
            $nom_complet .= implode(' ', $prenoms);

            return $nom_complet;
        }, $demandeurs);

        return implode(' / ', $demandeurs_names);
    }
}
