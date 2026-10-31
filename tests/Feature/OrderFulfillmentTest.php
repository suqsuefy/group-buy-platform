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

class OrderFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private function setupOrder(): array
    {
        $vendor=User::create(['name'=>'Vendor','email'=>'vendor@example.test','phone'=>'01000000000','password'=>bcrypt('password123'),'role'=>'vendor','status'=>'active']);
        $customer=User::create(['name'=>'Customer','email'=>'customer@example.test','phone'=>'01000000001','password'=>bcrypt('password123'),'role'=>'customer','status'=>'active']);
        $admin=User::create(['name'=>'Admin','email'=>'admin@example.test','phone'=>'01000000002','password'=>bcrypt('password123'),'role'=>'admin','status'=>'active']);
        $store=Store::create(['user_id'=>$vendor->id,'name'=>'Seller','slug'=>'seller','status'=>'active']);
        $product=Product::create(['store_id'=>$store->id,'title'=>'Product','slug'=>'item','regular_price'=>100,'stock'=>10,'status'=>'active']);
        $campaign=Campaign::create(['store_id'=>$store->id,'product_id'=>$product->id,'title'=>'Campaign','regular_price'=>100,'group_price'=>70,'min_participants'=>2,'target_participants'=>5,'current_participants'=>1,'start_at'=>now()->subHour(),'end_at'=>now()->addDay(),'status'=>'fulfilled']);
        $campaign->participants()->create(['user_id'=>$customer->id,'quantity'=>2,'status'=>'joined']);
        $address=Address::create(['user_id'=>$customer->id,'title'=>'Home','city'=>'Cairo','area'=>'Dokki','phone'=>'01000000001']);
        $this->actingAs($customer,'sanctum')->postJson('/api/orders',[
            'campaign_id'=>$campaign->id,'address_id'=>$address->id,'payment_method'=>'cash_on_delivery',
        ])->assertCreated();
        $order=Order::firstOrFail();
        return compact('vendor','customer','admin','store','product','campaign','order');
    }

    public function test_vendor_can_process_ship_deliver_but_cannot_collect_cod(): void
    {
        $f=$this->setupOrder();
        $url='/api/orders/'.$f['order']->id.'/transition';
        $this->actingAs($f['vendor'],'sanctum')->postJson($url,['action'=>'deliver'])->assertUnprocessable();
        $this->postJson($url,['action'=>'process'])->assertOk()->assertJsonPath('order.status','processing');
        $this->postJson($url,['action'=>'ship'])->assertUnprocessable();
        $this->postJson($url,['action'=>'ship','delivery_reference'=>'SHIP-100'])->assertOk()->assertJsonPath('order.status','shipped');
        $this->postJson($url,['action'=>'deliver'])->assertOk()->assertJsonPath('order.status','delivered');
        $this->postJson('/api/admin/orders/'.$f['order']->id.'/collect-cod')->assertForbidden();
        $this->assertDatabaseHas('orders',['id'=>$f['order']->id,'status'=>'delivered']);
        $this->assertNull($f['order']->fresh()->cod_collected_at);
    }

    public function test_only_admin_can_collect_cod_once_and_revenue_updates(): void
    {
        $f=$this->setupOrder();
        $url='/api/orders/'.$f['order']->id.'/transition';
        $this->actingAs($f['vendor'],'sanctum')->postJson($url,['action'=>'process'])->assertOk();
        $this->postJson($url,['action'=>'ship','delivery_reference'=>'SHIP-1'])->assertOk();
        $this->postJson($url,['action'=>'deliver'])->assertOk();
        $this->actingAs($f['admin'],'sanctum')->getJson('/api/admin/overview')->assertOk()
            ->assertJsonPath('revenue',0);
        $collect='/api/admin/orders/'.$f['order']->id.'/collect-cod';
        $this->postJson($collect)->assertOk();
        $this->postJson($collect)->assertUnprocessable();
        $this->getJson('/api/admin/overview')->assertOk()->assertJsonPath('revenue',140);
    }

    public function test_customer_cannot_change_order_status_and_vendor_cannot_manage_other_store(): void
    {
        $f=$this->setupOrder();
        $url='/api/orders/'.$f['order']->id.'/transition';
        $this->actingAs($f['customer'],'sanctum')->postJson($url,['action'=>'process'])->assertForbidden();
        $other=User::create(['name'=>'Other','email'=>'other@example.test','phone'=>'01000000003','password'=>bcrypt('password123'),'role'=>'vendor','status'=>'active']);
        $this->actingAs($other,'sanctum')->postJson($url,['action'=>'process'])->assertForbidden();
        $this->assertSame('pending',$f['order']->fresh()->status);
    }

    public function test_cancelled_order_restores_stock_exactly_once(): void
    {
        $f=$this->setupOrder();
        $this->assertSame(8,(int)$f['product']->fresh()->stock);
        $url='/api/orders/'.$f['order']->id.'/transition';
        $this->actingAs($f['vendor'],'sanctum')->postJson($url,['action'=>'cancel','reason'=>'Customer requested'])->assertOk();
        $this->assertSame(10,(int)$f['product']->fresh()->stock);
        $this->postJson($url,['action'=>'cancel'])->assertUnprocessable();
        $this->postJson($url,['action'=>'ship','delivery_reference'=>'SHIP'])->assertUnprocessable();
        $this->assertSame(10,(int)$f['product']->fresh()->stock);
        $this->assertSame('cancelled',$f['order']->fresh()->status);
    }

    public function test_shipped_order_cannot_be_cancelled_by_standard_transition(): void
    {
        $f=$this->setupOrder();
        $url='/api/orders/'.$f['order']->id.'/transition';
        $this->actingAs($f['vendor'],'sanctum')->postJson($url,['action'=>'process'])->assertOk();
        $this->postJson($url,['action'=>'ship','delivery_reference'=>'SHIP'])->assertOk();
        $this->postJson($url,['action'=>'cancel'])->assertUnprocessable();
        $this->assertSame(8,(int)$f['product']->fresh()->stock);
    }
}
