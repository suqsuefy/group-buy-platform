<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogModerationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $admin=User::create(['name'=>'Admin','email'=>'admin@test.local','password'=>bcrypt('password123'),'role'=>'admin','status'=>'active']);
        $vendor=User::create(['name'=>'Vendor','email'=>'vendor@test.local','password'=>bcrypt('password123'),'role'=>'vendor','status'=>'active']);
        $store=Store::create(['name'=>'My Store','slug'=>'my-store','user_id'=>$vendor->id,'status'=>'pending']);
        return compact('admin','vendor','store');
    }

    public function test_only_admin_can_approve_store_and_product(): void
    {
        $f=$this->fixture();
        $product=Product::create(['store_id'=>$f['store']->id,'title'=>'Item','slug'=>'item','regular_price'=>100,'stock'=>10,'status'=>'draft']);
        $this->actingAs($f['vendor'],'web')->post('/dashboard/stores/'.$f['store']->id.'/moderate',['status'=>'active'])->assertForbidden();
        $this->post('/dashboard/products/'.$product->id.'/moderate',['status'=>'active'])->assertForbidden();
        $this->actingAs($f['admin'],'web')->post('/dashboard/products/'.$product->id.'/moderate',['status'=>'active'])->assertRedirect();
        $this->assertSame('draft',$product->fresh()->status);
        $this->post('/dashboard/stores/'.$f['store']->id.'/moderate',['status'=>'active'])->assertRedirect();
        $this->assertSame('active',$f['store']->fresh()->status);
        $this->post('/dashboard/products/'.$product->id.'/moderate',['status'=>'active'])->assertRedirect();
        $this->assertSame('active',$product->fresh()->status);
        $this->assertDatabaseCount('catalog_moderation_events',2);
    }

    public function test_vendor_product_submission_stays_unpublished_until_admin_approval(): void
    {
        $f=$this->fixture();
        $f['store']->update(['status'=>'active']);
        $this->actingAs($f['vendor'],'sanctum')->postJson('/api/products',[
            'store_id'=>$f['store']->id,'title'=>'Submitted item','regular_price'=>100,'stock'=>10,
        ])->assertCreated()->assertJsonPath('product.status','draft');
        $this->getJson('/api/products')->assertOk()->assertJsonCount(0);
    }

    public function test_store_cannot_be_blocked_with_active_campaigns(): void
    {
        $f=$this->fixture();
        $f['store']->update(['status'=>'active']);
        $product=Product::create(['store_id'=>$f['store']->id,'title'=>'Item','slug'=>'item','regular_price'=>100,'stock'=>10,'status'=>'active']);
        Campaign::create(['store_id'=>$f['store']->id,'product_id'=>$product->id,'title'=>'Deal','regular_price'=>100,'group_price'=>80,'min_participants'=>2,'target_participants'=>5,'start_at'=>now()->subHour(),'end_at'=>now()->addDay(),'status'=>'active']);
        $this->actingAs($f['admin'],'web')->post('/dashboard/stores/'.$f['store']->id.'/moderate',['status'=>'blocked'])->assertRedirect();
        $this->assertSame('active',$f['store']->fresh()->status);
        $this->post('/dashboard/products/'.$product->id.'/moderate',['status'=>'inactive'])->assertRedirect();
        $this->assertSame('active',$product->fresh()->status);
    }
}
