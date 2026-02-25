<?php

/**
 * Exemple : Récupérer la cote d'un jeu vidéo par son EAN
 *
 * Cet exemple interroge l'API Voxsoft pour obtenir l'argus complet
 * de Super Mario Land (Game Boy), incluant les prix loose/CIB,
 * les meilleures offres marketplaces, et l'historique de cotes.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Voxsoft\Client;
use Voxsoft\ApiException;

// --- Configuration ---
$apiKey = 'VOTRE_CLÉ_API';  // Remplacez par votre clé (obtenue sur voxsoft.fr)
$ean    = '0045496460402';   // Super Mario Land - Nintendo Classics

// --- Initialisation du client ---
$client = new Client($apiKey);

try {
    $data = $client->getByEan($ean);

    // Informations générales
    echo "=== {$data['title']} ===\n";
    echo "Plateforme : {$data['platform']['label']} ({$data['platform']['brand']})\n\n";

    // Cote Argus
    $buyback = $data['buyback'];
    echo "--- Cote Argus ---\n";
    echo "Argus CIB : {$buyback['argus']} {$buyback['currency']}\n";

    if ($buyback['is_retrogaming']) {
        echo "Loose     : {$buyback['retrogaming']['loose']} {$buyback['currency']}\n";
        echo "CIB       : {$buyback['retrogaming']['cib']} {$buyback['currency']}\n";
        echo "Support   : {$buyback['retrogaming']['support']}\n";
    }

    // Meilleures offres
    echo "\n--- Offres Marketplaces ---\n";
    foreach ($data['marketplaces']['offers'] as $offer) {
        $price = isset($offer['total'])
            ? number_format($offer['total'], 2) . " {$buyback['currency']}"
            : 'Voir le site';
        echo "{$offer['marketplace']} : {$price}\n";
    }

    if (!empty($data['marketplaces']['best'])) {
        $best = $data['marketplaces']['best'];
        echo "\nMeilleur prix : {$best['marketplace']} à {$best['total']} {$buyback['currency']}\n";
    }

    // Historique de cotes
    echo "\n--- Historique ---\n";
    foreach ($data['history']['months'] as $month) {
        echo "{$month['period']} : {$month['median_buyback']} {$buyback['currency']}\n";
    }

} catch (ApiException $e) {
    echo "Erreur API ({$e->getErrorCode()}) : {$e->getMessage()}\n";
    echo "HTTP {$e->getHttpCode()}\n";

} catch (\Exception $e) {
    echo "Erreur : {$e->getMessage()}\n";
}
