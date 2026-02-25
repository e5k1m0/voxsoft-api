<?php

declare(strict_types=1);

namespace Voxsoft;

use RuntimeException;
use InvalidArgumentException;

/**
 * Voxsoft API Client
 *
 * Client PHP officiel pour l'API Voxsoft — accédez à l'argus et à la cote
 * des jeux vidéo en temps réel.
 *
 * @see https://www.voxsoft.fr Documentation API
 * @see https://www.voxgaming.fr Plateforme communautaire
 */
class Client
{
    private const BASE_URL = 'https://api.voxsoft.fr';
    private const VERSION = '1.0.0';
    private const TIMEOUT = 15;

    private string $apiKey;
    private int $timeout;

    /**
     * @param string $apiKey  Clé API Bearer (obtenue sur voxsoft.fr)
     * @param int    $timeout Timeout cURL en secondes (défaut : 15)
     *
     * @throws InvalidArgumentException Si la clé API est vide
     */
    public function __construct(string $apiKey, int $timeout = self::TIMEOUT)
    {
        if (empty(trim($apiKey))) {
            throw new InvalidArgumentException('La clé API ne peut pas être vide.');
        }

        $this->apiKey  = trim($apiKey);
        $this->timeout = $timeout;
    }

    /**
     * Récupère la cote et les informations d'un jeu par son EAN.
     *
     * Retourne les données complètes : buyback (loose/cib), offres marketplaces,
     * historique de prix, et métadonnées.
     *
     * @param string $ean Code EAN-13 du jeu (13 chiffres)
     *
     * @return array{
     *     success: bool,
     *     ean: string,
     *     title: string,
     *     platform: array{code: string, label: string, brand: string, support: string},
     *     identifiers: array{asin: string, rakutenId: string},
     *     buyback: array{currency: string, argus: int, source: string, is_retrogaming: int, retrogaming: array},
     *     marketplaces: array{offers: array, best: array},
     *     images: array{cover: string, platform: string},
     *     history: array{months: array},
     *     meta: array{version: string, updated_at: string, cache_ttl_seconds: int, request_id: string, credits_used: int}
     * }
     *
     * @throws InvalidArgumentException Si l'EAN est invalide
     * @throws RuntimeException         Si la requête échoue
     */
    public function getByEan(string $ean): array
    {
        $ean = trim($ean);

        if (!preg_match('/^\d{13}$/', $ean)) {
            throw new InvalidArgumentException(
                "EAN invalide : « {$ean} ». Un EAN doit contenir exactement 13 chiffres."
            );
        }

        return $this->request('GET', "/v1/ean/{$ean}");
    }

    /**
     * Exécute une requête HTTP vers l'API Voxsoft.
     *
     * @param string $method   Méthode HTTP (GET, POST…)
     * @param string $endpoint Endpoint relatif (ex: /v1/ean/0045496460402)
     *
     * @return array Réponse JSON décodée
     *
     * @throws RuntimeException En cas d'erreur cURL ou de réponse non-JSON
     */
    private function request(string $method, string $endpoint): array
    {
        $url = self::BASE_URL . $endpoint;

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Accept: application/json',
                'User-Agent: voxsoft-php/' . self::VERSION,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        $errno    = curl_errno($ch);

        curl_close($ch);

        if ($errno !== 0) {
            throw new RuntimeException("Erreur cURL ({$errno}) : {$error}");
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Réponse API invalide (JSON) : ' . json_last_error_msg()
            );
        }

        if ($httpCode >= 400) {
            $message = $data['message'] ?? 'Erreur inconnue';
            $code    = $data['error'] ?? 'unknown';

            throw new ApiException($message, $httpCode, $code);
        }

        return $data;
    }
}
