<?php

namespace Tests\Unit\Models;

use App\Enums\LoanStatus;
use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanOverdueTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_overdue_true_when_due_date_passed_and_not_finished(): void
    {
        $loan = Loan::factory()->create([
            'loan_date' => now()->subDays(30),
            'due_date' => now()->subDays(1),
            'status' => LoanStatus::berjalan->value,
        ]);

        $this->assertTrue($loan->isOverdue());
    }

    public function test_is_overdue_false_when_no_due_date(): void
    {
        $loan = Loan::factory()->create([
            'due_date' => null,
            'status' => LoanStatus::berjalan->value,
        ]);

        $this->assertFalse($loan->isOverdue());
    }

    public function test_is_overdue_false_when_due_date_today(): void
    {
        $loan = Loan::factory()->create([
            'due_date' => now()->toDateString(),
            'status' => LoanStatus::berjalan->value,
        ]);

        $this->assertFalse($loan->isOverdue());
    }

    public function test_is_overdue_false_when_finished_even_if_past_due(): void
    {
        $loan = Loan::factory()->create([
            'due_date' => now()->subDays(5),
            'status' => LoanStatus::selesai->value,
        ]);

        $this->assertFalse($loan->isOverdue());
    }

    public function test_scope_overdue_returns_only_late_unfinished_loans(): void
    {
        $late = Loan::factory()->create([
            'due_date' => now()->subDays(2),
            'status' => LoanStatus::berjalan->value,
        ]);
        Loan::factory()->create([
            'due_date' => now()->subDays(2),
            'status' => LoanStatus::selesai->value,
        ]);
        Loan::factory()->create([
            'due_date' => null,
            'status' => LoanStatus::berjalan->value,
        ]);
        Loan::factory()->create([
            'due_date' => now()->addDays(5),
            'status' => LoanStatus::berjalan->value,
        ]);

        $result = Loan::query()->overdue()->pluck('id');

        $this->assertCount(1, $result);
        $this->assertSame($late->id, $result->first());
    }
}
