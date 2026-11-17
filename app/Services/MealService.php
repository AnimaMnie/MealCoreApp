<?php

namespace App\Services;

use App\Events\MealLogChanged;
use App\Exceptions\BusinessRuleException;
use App\Models\DailySummary;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dziennik posiłków użytkownika.
 */
class MealService
{
    public const MAX_ITEMS_PER_MEAL = 20;

    public function __construct(private readonly NutritionCalculator $calculator)
    {
    }

    public function list(User $user, ?string $date, ?string $type, int $perPage = 10): LengthAwarePaginator
    {
        return $user->meals()
            ->with('items.product')
            ->when($date, fn ($q) => $q->whereDate('meal_date', $date))
            ->when($type, fn ($q) => $q->where('meal_type', $type))
            ->orderByDesc('meal_date')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function dailySummary(User $user, string $date): array
    {
        $meals = $user->meals()->with('items.product')->whereDate('meal_date', $date)->get();

        return ['date' => $date] + $this->calculator->dailySummary($meals, $user->daily_calorie_goal);
    }

    public function create(User $user, array $data): Meal
    {
        $this->ensureNotInFuture($data['meal_date']);

        $meal = DB::transaction(function () use ($user, $data) {
            $meal = $user->meals()->create([
                'meal_date' => $data['meal_date'],
                'meal_type' => $data['meal_type'],
                'name' => $data['name'] ?? null,
            ]);
            $meal->items()->createMany($data['items']);

            return $meal->load('items.product');
        });

        // Event po zatwierdzeniu transakcji -> listener w kolejce przelicza statystyki dnia.
        MealLogChanged::dispatch($user->id, $meal->meal_date->toDateString());

        return $meal;
    }

    /**
     * Jeśli przekazano "items", lista pozycji jest zastępowana w całości.
     */
    public function update(Meal $meal, array $data): Meal
    {
        if (isset($data['meal_date'])) {
            $this->ensureNotInFuture($data['meal_date']);
        }

        $oldDate = $meal->meal_date->toDateString();

        $meal = DB::transaction(function () use ($meal, $data) {
            $meal->update(collect($data)->only(['meal_date', 'meal_type', 'name'])->all());

            if (array_key_exists('items', $data)) {
                $meal->items()->delete();
                $meal->items()->createMany($data['items']);
            }

            return $meal->refresh()->load('items.product');
        });

        $newDate = $meal->meal_date->toDateString();
        MealLogChanged::dispatch($meal->user_id, $newDate);
        if ($oldDate !== $newDate) {
            MealLogChanged::dispatch($meal->user_id, $oldDate); // posiłek przeniesiony na inny dzień
        }

        return $meal;
    }

    public function delete(Meal $meal): void
    {
        $userId = $meal->user_id;
        $date = $meal->meal_date->toDateString();

        $meal->delete();

        MealLogChanged::dispatch($userId, $date);
    }

    /**
     * Historia ostatnich dni z tabeli daily_summaries (wypełnianej asynchronicznie).
     * Dni bez posiłków są zwracane z zerami, żeby frontend mógł narysować pełny wykres.
     */
    public function history(User $user, int $days): array
    {
        $from = today()->subDays($days - 1);

        $saved = DailySummary::where('user_id', $user->id)
            ->whereDate('date', '>=', $from)
            ->get()
            ->keyBy(fn (DailySummary $s) => $s->date->toDateString());

        $result = [];
        for ($day = $from->copy(); $day->lte(today()); $day->addDay()) {
            $key = $day->toDateString();
            $s = $saved->get($key);
            $result[] = [
                'date' => $key,
                'meals_count' => $s->meals_count ?? 0,
                'kcal' => $s->kcal ?? 0.0,
                'protein' => $s->protein ?? 0.0,
                'carbs' => $s->carbs ?? 0.0,
                'fat' => $s->fat ?? 0.0,
                'goal_exceeded' => $s->goal_exceeded ?? false,
            ];
        }

        $loggedDays = collect($result)->where('meals_count', '>', 0);

        return [
            'days' => $result,
            'calorie_goal' => $user->daily_calorie_goal,
            'average_kcal' => $loggedDays->isEmpty() ? 0.0 : round($loggedDays->avg('kcal'), 1),
            'days_logged' => $loggedDays->count(),
            'days_goal_exceeded' => $loggedDays->where('goal_exceeded', true)->count(),
        ];
    }

    /**
     * Reguła biznesowa: nie można zapisać posiłku z przyszłą datą
     * (dziennik opisuje to, co zostało zjedzone).
     */
    public function ensureNotInFuture(string $date): void
    {
        if (Carbon::parse($date)->startOfDay()->isAfter(today())) {
            throw new BusinessRuleException('Nie można zapisać posiłku z przyszłą datą.', 422);
        }
    }
}
