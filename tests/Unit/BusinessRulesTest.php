<?php

namespace Tests\Unit;

use App\Exceptions\BusinessRuleException;
use App\Models\User;
use App\Services\MealService;
use App\Services\NutritionCalculator;
use App\Services\UserManagementService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Testy jednostkowe reguł biznesowych w serwisach.
 * Reguły są sprawdzane zanim serwis dotknie bazy, więc testy nie zapisują nic do bazy.
 */
class BusinessRulesTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function userWithId(int $id, string $role = User::ROLE_ADMIN): User
    {
        $user = new User;
        $user->id = $id;
        $user->role = $role;

        return $user;
    }

    public function test_meal_cannot_be_logged_in_the_future(): void
    {
        Carbon::setTestNow('2026-11-20 12:00:00');
        $service = new MealService(new NutritionCalculator);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('Nie można zapisać posiłku z przyszłą datą.');

        $service->ensureNotInFuture('2026-11-21');
    }

    public function test_meal_can_be_logged_today_and_in_the_past(): void
    {
        Carbon::setTestNow('2026-11-20 12:00:00');
        $service = new MealService(new NutritionCalculator);

        $service->ensureNotInFuture('2026-11-20');
        $service->ensureNotInFuture('2026-01-01');

        $this->addToAssertionCount(1); // brak wyjątku = reguła przepuszcza poprawne daty
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = $this->userWithId(1);

        try {
            (new UserManagementService)->delete($admin, $this->userWithId(1));
            $this->fail('Oczekiwano BusinessRuleException.');
        } catch (BusinessRuleException $e) {
            $this->assertSame(409, $e->status());
            $this->assertSame('Nie możesz usunąć własnego konta.', $e->getMessage());
        }
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $admin = $this->userWithId(1);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('Nie możesz odebrać uprawnień administratora samemu sobie.');

        (new UserManagementService)->changeRole($admin, $this->userWithId(1), User::ROLE_USER);
    }

    public function test_is_admin_depends_on_role(): void
    {
        $this->assertTrue($this->userWithId(1, User::ROLE_ADMIN)->isAdmin());
        $this->assertFalse($this->userWithId(2, User::ROLE_USER)->isAdmin());
    }
}
