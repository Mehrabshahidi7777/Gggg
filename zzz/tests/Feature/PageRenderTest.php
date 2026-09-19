<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\Category;
use App\Models\City;
use App\Models\Province;
use App\Models\ServicePlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| رندر شدن صفحه‌هایی که کارت آگهی دارند
|--------------------------------------------------------------------------
|
| ویجت ستاره روی همه‌ی این صفحه‌ها تزریق شده است. این تست‌ها فقط
| مطمئن می‌شوند که هیچ‌کدام بعد از تغییرات خطای رندر نمی‌دهند - هم
| برای مهمان و هم برای کاربر واردشده (چون ویجت برای این دو حالتِ
| متفاوت رندر می‌شود).
|
*/
class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create([
            'name' => 'فروشنده',
            'username' => 'seller',
            'mobile' => '09120000001',
            'password' => 'secret-password',
        ]);

        $province = Province::create(['name' => 'تهران', 'slug' => 'tehran']);
        $city = City::create(['name' => 'تهران', 'slug' => 'tehran', 'province_id' => $province->id]);

        foreach (['product', 'service'] as $type) {

            $category = Category::create([
                'name' => 'دسته ' . $type,
                'slug' => 'cat-' . $type,
                'type' => $type,
                'is_active' => true,
            ]);

            Ad::create([
                'user_id' => $this->owner->id,
                'category_id' => $category->id,
                'province_id' => $province->id,
                'city_id' => $city->id,
                'type' => $type,
                'title' => 'آگهی ' . $type,
                'description' => 'توضیح',
                'price' => 5000,
                'address' => 'آدرس تست',
                'phone' => '09121234567',
                'status' => 'approved',
                'is_featured' => true,
            ]);
        }

        ServicePlan::create([
            'type' => 'service', 'months' => 1, 'title' => 'یک ماهه',
            'price' => 150000, 'is_active' => true, 'sort_order' => 1,
        ]);
        ServicePlan::create([
            'type' => 'product', 'months' => 1, 'title' => 'یک ماهه',
            'price' => 150000, 'is_active' => true, 'sort_order' => 1,
        ]);
    }

    public static function guestPages(): array
    {
        return [
            'home' => ['/'],
            'products' => ['/products'],
            'services' => ['/services'],
            'categories' => ['/categories'],
            'search' => ['/search?search=آگهی'],
            'about' => ['/about'],
            'contact' => ['/contact'],
        ];
    }

    #[DataProvider('guestPages')]
    public function test_public_pages_render_for_guests(string $url): void
    {
        $this->get($url)->assertOk();
    }

    #[DataProvider('guestPages')]
    public function test_public_pages_render_for_logged_in_users(string $url): void
    {
        $this->actingAs($this->owner)->get($url)->assertOk();
    }

    public function test_seller_profile_renders(): void
    {
        $this->get(route('seller.profile', $this->owner))->assertOk();
    }

    public function test_top_rated_sort_does_not_break_listing(): void
    {
        $this->get('/products?sort=top_rated')->assertOk();
        $this->get('/services?sort=top_rated')->assertOk();
    }

    public function test_provider_panels_render_with_new_titles(): void
    {
        $this->actingAs($this->owner)
            ->get(route('service.panel'))
            ->assertOk()
            ->assertSee('پنل ارائه خدمات', false);

        $this->actingAs($this->owner)
            ->get(route('product.panel'))
            ->assertOk()
            ->assertSee('پنل ارائه محصولات', false);
    }

    public function test_order_history_stays_reachable_while_checkout_is_disabled(): void
    {
        $this->actingAs($this->owner)
            ->get(route('orders.index'))
            ->assertOk();
    }
}
