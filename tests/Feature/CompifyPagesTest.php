<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompifyPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_store_pages_render_successfully(): void
    {
        Product::factory()->create(['is_featured' => true]);

        $this->get('/')->assertOk();
        $this->get('/products')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_admin_can_open_filament_management_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin);

        $this->get('/admin')->assertOk();
        $this->get('/admin/products')->assertOk();
        $this->get('/admin/products/create')->assertOk();
        $this->get('/admin/categories/create')->assertOk();
        $this->get('/admin/orders/create')->assertOk();
        $this->get('/admin/users/create')->assertOk();
        $this->get('/admin/banners/create')->assertOk();
        $this->get('/admin/testimonials/create')->assertOk();
    }
}
