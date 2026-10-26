<?php

namespace Tests\Feature\Admin;

use App\Models\Borrower;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BorrowerManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'identity_number' => '197001012000011001',
            'institution' => 'Sekretariat DPRD',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 1',
        ], $overrides);
    }

    public function test_guest_cannot_access_borrower_routes(): void
    {
        $borrower = Borrower::factory()->create();

        $this->get('/admin/peminjam')->assertRedirect('/login');
        $this->post('/admin/peminjam', $this->validPayload())->assertRedirect('/login');
        $this->get("/admin/peminjam/{$borrower->id}")->assertRedirect('/login');
        $this->put("/admin/peminjam/{$borrower->id}", ['name' => 'X'])->assertRedirect('/login');
        $this->delete("/admin/peminjam/{$borrower->id}")->assertRedirect('/login');
    }

    public function test_index_lists_borrowers_with_loan_count(): void
    {
        $borrower = Borrower::factory()->create([
            'name' => 'Budi Santoso',
            'identity_number' => '197001012000011001',
        ]);

        $user = $this->admin();

        Loan::factory()->create([
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/admin/peminjam');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Peminjam/Index')
            ->has('borrowers.data', 1)
            ->where('borrowers.data.0.name', 'Budi Santoso')
            ->where('borrowers.data.0.loans_count', 1)
            ->where('filters.search', '')
        );
    }

    public function test_index_search_matches_name(): void
    {
        Borrower::factory()->create(['name' => 'Budi Santoso']);
        Borrower::factory()->create(['name' => 'Siti Aminah']);

        $response = $this->actingAs($this->admin())->get('/admin/peminjam?search=Budi');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('borrowers.data', 1)
            ->where('borrowers.data.0.name', 'Budi Santoso')
            ->where('filters.search', 'Budi')
        );
    }

    public function test_index_search_matches_identity_number(): void
    {
        Borrower::factory()->create([
            'name' => 'Budi Santoso',
            'identity_number' => '197001012000011001',
        ]);
        Borrower::factory()->create([
            'name' => 'Siti Aminah',
            'identity_number' => '198505052010012002',
        ]);

        $response = $this->actingAs($this->admin())
            ->get('/admin/peminjam?search=19850505');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('borrowers.data', 1)
            ->where('borrowers.data.0.name', 'Siti Aminah')
        );
    }

    public function test_index_paginates_and_keeps_search_query_string(): void
    {
        Borrower::factory()->count(12)->create(['name' => 'Peminjam Uji']);

        $response = $this->actingAs($this->admin())
            ->get('/admin/peminjam?search=Peminjam Uji');

        $response->assertOk();
        $response->assertInertia(function (Assert $page) {
            $page->has('borrowers.data', config('library.per_page'))
                ->where('borrowers.total', 12)
                ->where('borrowers.last_page', 2);

            $links = $page->toArray()['props']['borrowers']['links'];
            $halamanDua = collect($links)->firstWhere('label', '2');

            $this->assertNotNull($halamanDua);
            $this->assertStringContainsString('search=Peminjam%20Uji', $halamanDua['url']);
            $this->assertStringContainsString('page=2', $halamanDua['url']);
        });
    }

    public function test_store_creates_borrower(): void
    {
        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjam')
            ->post('/admin/peminjam', $this->validPayload());

        $response->assertRedirect('/admin/peminjam');
        $response->assertSessionHas('success', 'Peminjam berhasil ditambahkan.');

        $this->assertDatabaseHas('borrowers', [
            'name' => 'Budi Santoso',
            'identity_number' => '197001012000011001',
            'institution' => 'Sekretariat DPRD',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 1',
        ]);
    }

    public function test_store_allows_empty_optional_fields(): void
    {
        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjam')
            ->post('/admin/peminjam', $this->validPayload([
                'institution' => '',
                'phone' => '',
                'address' => '',
            ]));

        $response->assertSessionHasNoErrors();

        $borrower = Borrower::query()
            ->where('identity_number', '197001012000011001')
            ->firstOrFail();

        $this->assertNull($borrower->institution);
        $this->assertNull($borrower->phone);
        $this->assertNull($borrower->address);
    }

    public function test_store_requires_name_and_identity_number(): void
    {
        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjam')
            ->post('/admin/peminjam', [
                'name' => '',
                'identity_number' => '',
            ]);

        $response->assertSessionHasErrors([
            'name' => 'Nama peminjam wajib diisi.',
            'identity_number' => 'Nomor identitas wajib diisi.',
        ]);

        $this->assertDatabaseCount('borrowers', 0);
    }

    public function test_store_rejects_duplicate_identity_number(): void
    {
        Borrower::factory()->create(['identity_number' => '197001012000011001']);

        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjam')
            ->post('/admin/peminjam', $this->validPayload());

        $response->assertSessionHasErrors([
            'identity_number' => 'Nomor identitas sudah terdaftar.',
        ]);

        $this->assertDatabaseCount('borrowers', 1);
    }

    public function test_update_changes_borrower_fields(): void
    {
        $borrower = Borrower::factory()->create([
            'name' => 'Nama Lama',
            'identity_number' => '111',
        ]);

        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjam')
            ->put("/admin/peminjam/{$borrower->id}", [
                'name' => 'Nama Baru',
                'identity_number' => '222',
                'institution' => 'Komisi B',
                'phone' => '089876543210',
                'address' => 'Jl. Baru No. 2',
            ]);

        $response->assertRedirect('/admin/peminjam');
        $response->assertSessionHas('success', 'Data peminjam berhasil diperbarui.');

        $this->assertDatabaseHas('borrowers', [
            'id' => $borrower->id,
            'name' => 'Nama Baru',
            'identity_number' => '222',
            'institution' => 'Komisi B',
        ]);
    }

    public function test_update_allows_same_identity_number_for_same_borrower(): void
    {
        $borrower = Borrower::factory()->create(['identity_number' => '111']);

        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjam')
            ->put("/admin/peminjam/{$borrower->id}", [
                'name' => 'Nama Baru',
                'identity_number' => '111',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
    }

    public function test_update_rejects_duplicate_identity_number(): void
    {
        Borrower::factory()->create(['identity_number' => '111']);
        $target = Borrower::factory()->create(['identity_number' => '222']);

        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjam')
            ->put("/admin/peminjam/{$target->id}", [
                'name' => 'Nama Baru',
                'identity_number' => '111',
            ]);

        $response->assertSessionHasErrors([
            'identity_number' => 'Nomor identitas sudah terdaftar.',
        ]);

        $this->assertDatabaseHas('borrowers', [
            'id' => $target->id,
            'identity_number' => '222',
        ]);
    }

    public function test_show_displays_borrower_profile_and_empty_loan_history(): void
    {
        $borrower = Borrower::factory()->create([
            'name' => 'Budi Santoso',
            'identity_number' => '197001012000011001',
            'institution' => 'Sekretariat DPRD',
            'phone' => '081234567890',
            'address' => 'Jl. Merdeka No. 1',
        ]);

        $response = $this->actingAs($this->admin())
            ->get("/admin/peminjam/{$borrower->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Peminjam/Show')
            ->where('borrower.name', 'Budi Santoso')
            ->where('borrower.identity_number', '197001012000011001')
            ->where('borrower.institution', 'Sekretariat DPRD')
            ->where('borrower.phone', '081234567890')
            ->where('borrower.address', 'Jl. Merdeka No. 1')
            ->has('loans', 0)
        );
    }

    public function test_show_lists_loan_history_when_present(): void
    {
        $user = $this->admin();
        $borrower = Borrower::factory()->create();

        $loan = Loan::factory()->create([
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
            'loan_number' => 'LN-000001',
        ]);

        LoanItem::factory()->create([
            'loan_id' => $loan->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($user)
            ->get("/admin/peminjam/{$borrower->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('loans', 1)
            ->where('loans.0.loan_number', 'LN-000001')
            ->where('loans.0.jumlah_judul', 1)
            ->where('loans.0.jumlah_eksemplar', 2)
            ->where('loans.0.status', 'berjalan')
            ->where('loans.0.petugas', $user->name)
        );
    }

    public function test_show_returns_not_found_for_unknown_borrower(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/peminjam/999999')
            ->assertNotFound();
    }

    public function test_destroy_deletes_borrower_without_transactions(): void
    {
        $borrower = Borrower::factory()->create();

        $response = $this->actingAs($this->admin())
            ->from('/admin/peminjam')
            ->delete("/admin/peminjam/{$borrower->id}");

        $response->assertRedirect('/admin/peminjam');
        $response->assertSessionHas('success', 'Peminjam berhasil dihapus.');

        $this->assertDatabaseMissing('borrowers', ['id' => $borrower->id]);
    }

    public function test_destroy_is_rejected_when_borrower_has_transactions(): void
    {
        $user = $this->admin();
        $borrower = Borrower::factory()->create();

        Loan::factory()->create([
            'borrower_id' => $borrower->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->from('/admin/peminjam')
            ->delete("/admin/peminjam/{$borrower->id}");

        $response->assertRedirect('/admin/peminjam');
        $response->assertSessionHas(
            'error',
            'Peminjam tidak dapat dihapus karena memiliki riwayat peminjaman.',
        );
        $response->assertSessionMissing('success');

        $this->assertDatabaseHas('borrowers', ['id' => $borrower->id]);
    }
}
