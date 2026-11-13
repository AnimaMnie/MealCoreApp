<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMealRequest;
use App\Http\Requests\UpdateMealRequest;
use App\Http\Resources\MealResource;
use App\Models\Meal;
use App\Services\MealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class MealController extends Controller
{
    public function __construct(private readonly MealService $meals)
    {
    }

    /**
     * Posiłki zalogowanego użytkownika (paginacja + filtr daty i rodzaju).
     *
     * @queryParam date string Dzień w formacie Y-m-d. Example: 2026-11-20
     * @queryParam meal_type string Rodzaj posiłku. Example: lunch
     * @queryParam per_page int Liczba wyników na stronę (1-50). Example: 10
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'meal_type' => ['nullable', Rule::in(Meal::TYPES)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return MealResource::collection($this->meals->list(
            $request->user(),
            $validated['date'] ?? null,
            $validated['meal_type'] ?? null,
            $validated['per_page'] ?? 10,
        ));
    }

    /**
     * Dzienne podsumowanie: suma kcal i makro oraz ile zostało do celu.
     *
     * @queryParam date string Dzień w formacie Y-m-d (domyślnie dziś). Example: 2026-11-20
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        return response()->json([
            'data' => $this->meals->dailySummary($request->user(), $validated['date'] ?? today()->toDateString()),
        ]);
    }

    public function store(StoreMealRequest $request): MealResource
    {
        return new MealResource($this->meals->create($request->user(), $request->validated()));
    }

    public function show(Meal $meal): MealResource
    {
        return new MealResource($meal->load('items.product'));
    }

    public function update(UpdateMealRequest $request, Meal $meal): MealResource
    {
        return new MealResource($this->meals->update($meal, $request->validated()));
    }

    public function destroy(Meal $meal): Response
    {
        $this->meals->delete($meal);

        return response()->noContent();
    }
}
