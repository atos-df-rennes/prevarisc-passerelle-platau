<?php

namespace App\Service;

final class PlatauNomenclature extends PlatauAbstract
{

  /**
   * Recherche la liste des nomenclatures par rapport au $codeNomenclature.
   * Filtre ensuite cette liste pour retourner uniquement la nomenclature
   * correspondant à $idNomenclature.
   *
   * @param string $codeNomenclature
   *  Par exemple : TYPE_OBJET_METIER
   * @param int $idNomenclature
   *  Par exemple : 19
   *
   * @return string
   *  Le libellé de la nomenclature recherchée.
   */
  public function rechercheNomenclature(string $codeNomenclature, int $idNomenclature): string
  {
    $response = $this->request('get', 'nomenclatures/' . $codeNomenclature);

    $nomenclatures = json_decode($response->getBody()->__toString(), true, 512, \JSON_THROW_ON_ERROR);

    if (!\is_array($nomenclatures)) {
      throw new \Exception('Un problème a eu lieu dans la récupération des résultats de recherche de nomenclatures : le résultat est incorrect');
    }

    $nomenclature = array_filter($nomenclatures, fn (array $nomenclature) => $nomenclature['idNom'] === $idNomenclature);

    if ([] === $nomenclature) {
      throw new \Exception(\sprintf("Aucune nomenclature trouvée pour l'identifiant %d et le code %s", $idNomenclature, $codeNomenclature));
    }

    return $nomenclature[array_key_first($nomenclature)]['libNom'];
  }
}