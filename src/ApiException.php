<?php

declare(strict_types=1);

namespace Voxsoft;

use RuntimeException;

/**
 * Exception spécifique aux erreurs retournées par l'API Voxsoft.
 */
class ApiException extends RuntimeException
{
    private string $errorCode;

    public function __construct(string $message, int $httpCode, string $errorCode)
    {
        $this->errorCode = $errorCode;
        parent::__construct($message, $httpCode);
    }

    /**
     * Code d'erreur API (ex: not_found, invalid_ean, rate_limited…)
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Code HTTP de la réponse (400, 401, 404, 429…)
     */
    public function getHttpCode(): int
    {
        return $this->getCode();
    }
}
