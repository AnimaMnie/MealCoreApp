<?php

namespace App\Listeners;

use App\Events\MealLogChanged;
use App\Models\DailySummary;
use App\Models\User;
use App\Services\MealService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Mechanizm asynchroniczny: listener działa w kolejce (ShouldQueue), więc request
 * zapisujący posiłek nie czeka na przeliczenie statystyk. Zadanie wykonuje
 * kontener "queue" (php artisan queue:work). Laravel sam wiąże listener z eventem
 * na podstawie typu parametru metody handle().
 */
class UpdateDailySummary implements ShouldQueue
{
    public int $tries = 3;

    public function __construct(private readonly MealService $meals)
    {
    }

    public function handle(MealLogChanged $event): void
    {
        $user = User::find($event->userId);

        if (! $user) {
            return; // konto usunięte zanim zadanie się wykonało
        }

        $summary = $this->meals->dailySummary($user, $event->date);

        if ($summary['meals_count'] === 0) {
            DailySummary::where('user_id', $user->id)->whereDate('date', $event->date)->delete();

            return;
        }

        DailySummary::updateOrCreate(
            ['user_id' => $user->id, 'date' => $event->date],
            [
                'meals_count' => $summary['meals_count'],
                'kcal' => $summary['totals']['kcal'],
                'protein' => $summary['totals']['protein'],
                'carbs' => $summary['totals']['carbs'],
                'fat' => $summary['totals']['fat'],
                'calorie_goal' => $summary['calorie_goal'],
                'goal_exceeded' => $summary['goal_exceeded'],
            ],
        );
    }
}
