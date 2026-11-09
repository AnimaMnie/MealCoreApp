<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Naruszenie reguły biznesowej (np. usunięcie produktu użytego w posiłku).
 * Globalny handler w bootstrap/app.php zamienia ją na odpowiedź JSON.
 */
class BusinessRuleException extends RuntimeException
{
    public function __construct(string $message, private readonly int $status = 409)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
