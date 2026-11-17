<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event: dziennik posiłków użytkownika zmienił się w danym dniu
 * (dodanie, edycja lub usunięcie posiłku).
 */
class MealLogChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $date,
    ) {
    }
}
