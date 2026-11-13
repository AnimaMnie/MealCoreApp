<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
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

        return DB::transaction(function () use ($user, $data) {
            $meal = $user->meals()->create([
                'meal_date' => $data['meal_date'],
                'meal_type' => $data['meal_type'],
                'name' => $data['name'] ?? null,
            ]);
            $meal->items()->createMany($data['items']);

            return $meal->load('items.product');
        });
    }

    /**
     * Jeśli przekazano "items", lista pozycji jest zastępowana w całości.
     */
    public function update(Meal $meal, array $data): Meal
    {
        if (isset($data['meal_date'])) {
            $this->ensureNotInFuture($data['meal_date']);
        }

        return DB::transaction(function () use ($meal, $data) {
            $meal->update(collect($data)->only(['meal_date', 'meal_type', 'name'])->all());

            if (array_key_exists('items', $data)) {
                $meal->items()->delete();
                $meal->items()->createMany($data['items']);
            }

            return $meal->refresh()->load('items.product');
        });
    }

    public function delete(Meal $meal): void
    {
        $meal->delete();
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
