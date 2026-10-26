<?php

namespace Tests\Feature\Admin;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_guest_cannot_access_category_routes(): void
    {
        $category = Category::factory()->create();

        $this->get('/admin/kategori')->assertRedirect('/login');
        $this->post('/admin/kategori', ['name' => 'Baru'])->assertRedirect('/login');
        $this->put("/admin/kategori/{$category->id}", ['name' => 'Ubah'])->assertRedirect('/login');
        $this->delete("/admin/kategori/{$category->id}")->assertRedirect('/login');
    }

    public function test_index_lists_categories_with_book_count(): void
    {
        $category = Category::factory()->create(['name' => 'Fiksi']);
        Book::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->admin())->get('/admin/kategori');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Kategori/Index')
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Fiksi')
            ->where('categories.data.0.books_count', 1)
            ->where('filters.search', '')
        );
    }

    public function test_index_filters_categories_by_search(): void
    {
        Category::factory()->create(['name' => 'Fiksi']);
        Category::factory()->create(['name' => 'Sejarah']);

        $response = $this->actingAs($this->admin())
            ->get('/admin/kategori?search=Sej');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('categories.data', 1)
            ->where('categories.data.0.name', 'Sejarah')
            ->where('filters.search', 'Sej')
        );
    }

    public function test_index_paginates_categories(): void
    {
        Category::factory()->count(12)->create();

        $response = $this->actingAs($this->admin())->get('/admin/kategori');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('categories.data', config('library.per_page'))
            ->where('categories.total', 12)
        );
    }

    public function test_store_creates_category(): void
    {
        $response = $this->actingAs($this->admin())
            ->from('/admin/kategori')
            ->post('/admin/kategori', ['name' => 'Fiksi']);

        $response->assertRedirect('/admin/kategori');
        $response->assertSessionHas('success', 'Kategori berhasil ditambahkan.');

        $this->assertDatabaseHas('categories', ['name' => 'Fiksi']);
    }

    public function test_store_rejects_duplicate_name_with_indonesian_message(): void
    {
        Category::factory()->create(['name' => 'Fiksi']);

        $response = $this->actingAs($this->admin())
            ->from('/admin/kategori')
            ->post('/admin/kategori', ['name' => 'Fiksi']);

        $response->assertSessionHasErrors([
            'name' => 'Nama kategori sudah digunakan.',
        ]);

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_store_requires_name(): void
    {
        $response = $this->actingAs($this->admin())
            ->from('/admin/kategori')
            ->post('/admin/kategori', ['name' => '']);

        $response->assertSessionHasErrors([
            'name' => 'Nama kategori wajib diisi.',
        ]);
    }

    public function test_update_renames_category(): void
    {
        $category = Category::factory()->create(['name' => 'Fiksi']);

        $response = $this->actingAs($this->admin())
            ->from('/admin/kategori')
            ->put("/admin/kategori/{$category->id}", ['name' => 'Fiksi Baru']);

        $response->assertRedirect('/admin/kategori');
        $response->assertSessionHas('success', 'Kategori berhasil diperbarui.');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Fiksi Baru',
        ]);
    }

    public function test_update_allows_same_name_for_same_category(): void
    {
        $category = Category::factory()->create(['name' => 'Fiksi']);

        $response = $this->actingAs($this->admin())
            ->from('/admin/kategori')
            ->put("/admin/kategori/{$category->id}", ['name' => 'Fiksi']);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
    }

    public function test_update_rejects_duplicate_name(): void
    {
        Category::factory()->create(['name' => 'Fiksi']);
        $target = Category::factory()->create(['name' => 'Sejarah']);

        $response = $this->actingAs($this->admin())
            ->from('/admin/kategori')
            ->put("/admin/kategori/{$target->id}", ['name' => 'Fiksi']);

        $response->assertSessionHasErrors([
            'name' => 'Nama kategori sudah digunakan.',
        ]);

        $this->assertDatabaseHas('categories', [
            'id' => $target->id,
            'name' => 'Sejarah',
        ]);
    }

    public function test_destroy_deletes_unused_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin())
            ->from('/admin/kategori')
            ->delete("/admin/kategori/{$category->id}");

        $response->assertRedirect('/admin/kategori');
        $response->assertSessionHas('success', 'Kategori berhasil dihapus.');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_destroy_is_rejected_when_category_still_has_books(): void
    {
        $category = Category::factory()->create();
        Book::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->admin())
            ->from('/admin/kategori')
            ->delete("/admin/kategori/{$category->id}");

        $response->assertRedirect('/admin/kategori');
        $response->assertSessionHas(
            'error',
            'Kategori tidak dapat dihapus karena masih dipakai oleh buku.',
        );
        $response->assertSessionMissing('success');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
