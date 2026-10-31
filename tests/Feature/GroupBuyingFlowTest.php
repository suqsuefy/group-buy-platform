<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Campaign;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupBuyingFlowTest extends TestCase
{
    use RefreshDatabase;

    private int $serial = 0;

    private function user(string $role = 'customer'): User
    {
        $this->serial++;
        return User::create([
            'name' => 'User '.$this->serial,
            'email' => 'u'.$this->serial.'@example.test',
            'phone' => '01000000000',
            'password' => bcrypt('password123'),
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function fixture(int $stock = 10, int $minimum = 2, int $target = 5): array
    {
        $seller = $this->user('vendor');
        $store = Store::create([
            'user_id' => $seller->id, 'name' => 'Test Store', 'slug' => 'store-'.$seller->id,
            'city' => 'Cairo', 'status' => 'active',
        ]);
        $product = Product::create([
            'store_id' => $store->id, 'title' => 'Test Product',
            'slug' => 'product-'.$seller->id, 'regular_price' => 100,
            'stock' => $stock, 'status' => 'active',
        ]);
        $campaign = Campaign::create([
            'store_id' => $store->id, 'product_id' => $product->id,
            'title' => 'Group Deal', 'regular_price' => 100, 'group_price' => 70,
            'min_participants' => $minimum, 'target_participants' => $target,
            'current_participants' => 0, 'start_at' => now()->subHour(),
            'end_at' => now()->addDay(), 'status' => 'active',
        ]);
        return compact('seller', 'store', 'product', 'campaign');
    }

    private function address(User $owner): Address
    {
        return Address::create([
            'user_id' => $owner->id, 'title' => 'Home', 'city' => 'Cairo',
            'area' => 'Dokki', 'phone' => '01012345678',
        ]);
    }

    private function join(User $user, Campaign $campaign, int $quantity = 1)
    {
        return $this->actingAs($user, 'sanctum')->postJson(
            '/api/campaigns/'.$campaign->id.'/join',
            ['quantity' => $quantity],
        );
    }

    public function test_only_store_owner_can_create_campaign(): void
    {
        $f = $this->fixture();
        $other = $this->user('vendor');
        $payload = [
            'store_id' => $f['store']->id, 'product_id' => $f['product']->id,
            'title' => 'New Campaign', 'regular_price' => 100,
            'group_price' => 70, 'min_participants' => 2,
            'target_participants' => 3,
            'start_at' => now()->toDateTimeString(),
            'end_at' => now()->addDay()->toDateTimeString(),
        ];
        $this->actingAs($other, 'sanctum')->postJson('/api/campaigns', $payload)->assertForbidden();
        $this->actingAs($f['seller'], 'sanctum')->postJson('/api/campaigns', $payload)->assertCreated();
    }

    public function test_participation_requires_authentication_and_open_dates(): void
    {
        $f = $this->fixture();
        $customer = $this->user();
        $this->postJson('/api/campaigns/'.$f['campaign']->id.'/join', ['quantity'=>1])->assertUnauthorized();
        $f['campaign']->update(['start_at'=>now()->addHour(), 'end_at'=>now()->addDay()]);
        $this->join($customer, $f['campaign'])->assertUnprocessable();
        $f['campaign']->update(['start_at'=>now()->subDay(), 'end_at'=>now()->subHour()]);
        $this->join($customer, $f['campaign'])->assertUnprocessable();
    }

    public function test_join_is_idempotent_for_network_retries(): void
    {
        $f = $this->fixture();
        $customer = $this->user();
        $this->join($customer, $f['campaign'], 2)->assertOk();
        $this->join($customer, $f['campaign'], 2)->assertOk();
        $this->assertDatabaseHas('campaign_participants', [
            'campaign_id' => $f['campaign']->id,
            'user_id' => $customer->id,
            'quantity' => 2,
        ]);
        $this->assertSame(1, (int) $f['campaign']->fresh()->current_participants);
    }

    public function test_target_and_stock_are_enforced(): void
    {
        $f = $this->fixture(stock: 2, minimum: 2, target: 2);
        $this->join($this->user(), $f['campaign'], 2)->assertOk();
        $this->join($this->user(), $f['campaign'], 1)->assertUnprocessable();
    }

    public function test_campaign_confirmation_needs_minimum_and_authorized_owner(): void
    {
        $f = $this->fixture();
        $customer = $this->user();
        $stranger = $this->user();
        $this->actingAs($stranger, 'sanctum')
            ->postJson('/api/campaigns/'.$f['campaign']->id.'/confirm')->assertForbidden();
        $this->actingAs($f['seller'], 'sanctum')
            ->postJson('/api/campaigns/'.$f['campaign']->id.'/confirm')->assertUnprocessable();
        $this->join($customer, $f['campaign'])->assertOk();
        $this->join($this->user(), $f['campaign'])->assertOk();
        $this->actingAs($f['seller'], 'sanctum')
            ->postJson('/api/campaigns/'.$f['campaign']->id.'/confirm')->assertOk();
        $this->assertSame('fulfilled', $f['campaign']->fresh()->status);
    }

    public function test_order_requires_confirmed_campaign_and_own_address(): void
    {
        $f = $this->fixture();
        $customer = $this->user();
        $other = $this->user();
        $address = $this->address($customer);
        $notMine = $this->address($other);
        $this->join($customer, $f['campaign'])->assertOk();

        $url = '/api/orders';
        $data = ['campaign_id'=>$f['campaign']->id, 'address_id'=>$address->id, 'payment_method'=>'cash_on_delivery'];

        $this->actingAs($customer, 'sanctum')->postJson($url, $data)->assertUnprocessable();
        $f['campaign']->update(['status'=>'fulfilled']);
        $this->actingAs($other, 'sanctum')->postJson($url, $data)->assertForbidden();
        $this->actingAs($customer, 'sanctum')->postJson($url, [
            ...$data, 'address_id'=>$notMine->id,
        ])->assertForbidden();
        $this->actingAs($customer, 'sanctum')->postJson($url, [
            ...$data, 'payment_method'=>'card',
        ])->assertUnprocessable();
    }

    public function test_confirmed_campaign_checkout_is_server_priced_once_and_tracks_stock(): void
    {
        $f = $this->fixture();
        $customer = $this->user();
        $this->join($customer, $f['campaign'], 2)->assertOk();
        $this->join($this->user(), $f['campaign'])->assertOk();
        $this->actingAs($f['seller'], 'sanctum')
            ->postJson('/api/campaigns/'.$f['campaign']->id.'/confirm')->assertOk();

        $address = $this->address($customer);
        $payload = [
            'campaign_id'=>$f['campaign']->id, 'address_id'=>$address->id,
            'payment_method'=>'cash_on_delivery', 'total'=>0.01,
        ];

        $this->actingAs($customer, 'sanctum')->postJson('/api/orders', $payload)
            ->assertCreated()->assertJsonPath('order.total', '140.00');
        $this->assertDatabaseHas('order_items', [
            'product_id'=>$f['product']->id, 'quantity'=>2, 'unit_price'=>70,
        ]);
        $this->assertSame(8, (int) $f['product']->fresh()->stock);
        $this->actingAs($customer, 'sanctum')->postJson('/api/orders', $payload)->assertUnprocessable();
        $this->assertSame(1, Order::where('user_id', $customer->id)->count());
        $this->actingAs($customer, 'sanctum')->getJson('/api/orders/my')->assertOk()->assertJsonCount(1);
    }

    public function test_more_than_available_stock_cannot_be_purchased(): void
    {
        $f = $this->fixture();
        $customer = $this->user();
        $this->join($customer, $f['campaign'], 3)->assertOk();
        $f['campaign']->update(['status'=>'fulfilled']);
        $f['product']->update(['stock'=>1]);
        $this->actingAs($customer, 'sanctum')->postJson('/api/orders', [
            'campaign_id'=>$f['campaign']->id,
            'address_id'=>$this->address($customer)->id,
            'payment_method'=>'cash_on_delivery',
        ])->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
    }
}
