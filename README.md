# Voxsoft API — Client PHP officiel

[![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-8892BF.svg)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Packagist](https://img.shields.io/badge/packagist-e5k1m0%2Fvoxsoft--api-orange)](https://packagist.org/packages/e5k1m0/voxsoft-api)

Client PHP pour l'**API Voxsoft** — accédez à la [cote argus des jeux vidéo](https://www.voxgaming.fr) de plus de 30 000 références de jeux vidéo sortis en France, toutes plateformes confondues de la SEGA Master System à la Nintendo Switch 2.

---

## Pourquoi Voxsoft ?

L'API [Voxsoft](https://www.voxsoft.fr) est l'infrastructure technique derrière **[VoxGaming](https://www.voxgaming.fr)**, plateforme communautaire dédiée aux jeux vidéo depuis plus de 4 ans.

Contrairement aux prix de mise en vente spéculatifs que l'on retrouve sur les marketplaces, les données Voxsoft sont **contrôlées et validées par des experts passionnés**. L'argus est calculé chaque mois à partir de transactions réelles, croisées sur plusieurs sources, et affinées par des algorithmes propriétaires tenant compte de l'ancienneté du jeu, des médianes glissantes et des volumes de vente.

Le résultat : un **prix de référence fiable** pour le marché de l'occasion et du retrogaming, utilisé par des milliers de collectionneurs, revendeurs et passionnés.

---

## Installation

```bash
composer require e5k1m0/voxsoft-api
```

**Pré-requis :** PHP 7.4+, extensions `curl` et `json`.

---

## Démarrage rapide

```php
<?php

require_once 'vendor/autoload.php';

use Voxsoft\Client;

$client = new Client('VOTRE_CLÉ_API');
$data   = $client->getByEan('0045496460402'); // Super Mario Land

echo $data['title'];                          // "Super Mario Land - Nintendo Classics"
echo $data['buyback']['argus'];               // 119 (EUR)
echo $data['buyback']['retrogaming']['loose']; // 31 (EUR)
```

> Obtenez votre clé API sur [voxsoft.fr](https://www.voxsoft.fr).

---

## Données retournées

Un appel à `getByEan()` retourne un tableau complet :

### Buyback (cote argus)

| Champ                       | Description                          |
| --------------------------- | ------------------------------------ |
| `buyback.argus`             | Cote argus officielle (CIB) en EUR   |
| `buyback.retrogaming.loose` | Prix loose (cartouche/disque seul)   |
| `buyback.retrogaming.cib`   | Prix CIB (complet en boîte)          |
| `buyback.source`            | Source de la cote (`argus`)          |
| `buyback.is_retrogaming`    | `1` si le jeu est classé retrogaming |

### Marketplaces (meilleures offres)

```json
{
  "marketplaces": {
    "offers": [
      {
        "marketplace": "amazon",
        "link": "https://www.amazon.fr/dp/B00004TMG4",
        "price": 50.86,
        "shipping": 2.99,
        "total": 53.85
      },
      {
        "marketplace": "rakuten",
        "link": "https://www.fr.shopping.rakuten.com/offer/buy/8599230366",
        "price": 49.9,
        "shipping": 0,
        "total": 49.9
      }
    ],
    "best": {
      "marketplace": "rakuten",
      "total": 49.9
    }
  }
}
```

### Historique de prix

L'historique mensuel permet de suivre l'évolution de la cote dans le temps :

```json
{
  "history": {
    "months": [
      { "period": "Octobre 24", "median_buyback": 28 },
      { "period": "Novembre 24", "median_buyback": 31 },
      { "period": "Décembre 24", "median_buyback": 44 }
    ]
  }
}
```

---

## Exemple complet

```php
<?php

require_once 'vendor/autoload.php';

use Voxsoft\Client;
use Voxsoft\ApiException;

$client = new Client('VOTRE_CLÉ_API');

try {
    $data = $client->getByEan('0045496460402');

    // Infos générales
    echo "{$data['title']} — {$data['platform']['label']}\n";

    // Cote argus
    $buyback = $data['buyback'];
    echo "Argus CIB : {$buyback['argus']} {$buyback['currency']}\n";

    if ($buyback['is_retrogaming']) {
        echo "Loose : {$buyback['retrogaming']['loose']} EUR\n";
        echo "CIB   : {$buyback['retrogaming']['cib']} EUR\n";
    }

    // Meilleur prix marketplace
    $best = $data['marketplaces']['best'];
    echo "Meilleur prix : {$best['marketplace']} — {$best['total']} EUR\n";

    // Historique
    foreach ($data['history']['months'] as $month) {
        echo "{$month['period']} : {$month['median_buyback']} EUR\n";
    }

} catch (ApiException $e) {
    // Erreur API (401, 404, 429…)
    echo "Erreur {$e->getHttpCode()} ({$e->getErrorCode()}) : {$e->getMessage()}\n";
}
```

Voir aussi [`examples/get_item.php`](examples/get_item.php) pour un script prêt à l'emploi.

---

## Gestion des erreurs

Le client lève des exceptions typées :

| Exception                  | Cas d'usage                           |
| -------------------------- | ------------------------------------- |
| `InvalidArgumentException` | Clé API vide ou EAN invalide          |
| `Voxsoft\ApiException`     | Erreur retournée par l'API (4xx, 5xx) |
| `RuntimeException`         | Erreur réseau (timeout, DNS…)         |

Codes d'erreur API :

| Code HTTP | `error`          | Description                              |
| --------- | ---------------- | ---------------------------------------- |
| 400       | `invalid_ean`    | EAN invalide (doit contenir 13 chiffres) |
| 401       | `unauthorized`   | Clé API manquante, invalide ou suspendue |
| 404       | `not_found`      | EAN non trouvé en base                   |
| 429       | `rate_limited`   | Quota dépassé (minute ou journalier)     |
| 502       | `upstream_error` | Erreur amont temporaire                  |

---

## Référence API

| Endpoint        | Méthode | Description                                                      |
| --------------- | ------- | ---------------------------------------------------------------- |
| `/v1/ean/{ean}` | `GET`   | Récupère la cote, les offres et l'historique d'un jeu par EAN-13 |

**Base URL :** `https://api.voxsoft.fr`

**Authentification :** Header `Authorization: Bearer VOTRE_CLÉ`

---

## À propos

**[VoxGaming](https://www.voxgaming.fr)** est une plateforme communautaire française de référence pour les passionnés de jeux vidéo. Depuis plus de 4 ans, elle propose un catalogue de 30 000+ jeux, un argus fiable basé sur des transactions réelles, une marketplace entre particuliers, et des outils communautaires (tier lists, forum, réseau social gaming).

**[Voxsoft](https://www.voxsoft.fr)** est l'infrastructure technique qui propulse les services VoxGaming : API de cotation, moteur de recherche, et pipelines de données.

---

## Licence

MIT — voir [LICENSE](LICENSE).
