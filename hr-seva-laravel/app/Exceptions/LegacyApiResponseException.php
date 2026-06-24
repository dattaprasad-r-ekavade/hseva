<?php

namespace App\Exceptions;

use Exception;

class LegacyApiResponseException extends Exception
{
    public function __construct(
        public readonly mixed $payload,
        public readonly int $status = 200,
    ) {
        parent::__construct((string) json_encode($payload), $status);
    }
}
