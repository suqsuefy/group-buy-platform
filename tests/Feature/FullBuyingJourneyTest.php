<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FullBuyingJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_seller_is_approved_then_two_buyers_purchase_and_cod_is_collected(): void
    {
        $sellerData=['name'=>'Seller','email'=>'seller@journey.test','phone'=>'01010000001','password'=>'secret1234'];
        $sellerResponse=$this->postJson('/api/register',$sellerData)->assertCreated()
            ->assertJsonPath('user.role','customer');
        $seller=User::where('email',$sellerData['email'])->firstOrFail();
        $this->withToken($sellerResponse->json('token'))->postJson('/api/stores',[
            'name'=>'Seller Shop','city'=>'Cairo',
        ])->assertCreated()->assertJsonPath('store.status','pending');
        $store=Store::where('user_id',$seller->id)->firstOrFail();
        $this->assertSame('customer',$seller->fresh()->role);
        $this->withToken($sellerResponse->json('token'))->postJson('/api/products',[
            'store_id'=>$store->id,'title'=>'Unapproved','regular_price'=>100,'stock'=>5,
        ])->assertForbidden();

        $admin=User::create([
            'name'=>'Administrator','email'=>'admin@journey.test','phone'=>'01010000002',
            'password'=>bcrypt('secret1234'),'role'=>'admin','status'=>'active',
        ]);
        $this->actingAs($admin,'web')->post('/dashboard/stores/'.$store->id.'/moderate',[
            'status'=>'active',
        ])->assertRedirect();
        $this->assertSame('vendor',$seller->fresh()->role);

        $this->actingAs($seller->fresh(),'web')->get('/dashboard')->assertOk()->assertSee('Seller Shop');
        $this->withToken($sellerResponse->json('token'))->postJson('/api/products',[
            'store_id'=>$store->id,'title'=>'Group Item','regular_price'=>100,'stock'=>5,
        ])->assertCreated()->assertJsonPath('product.status','draft');
        $product=Product::where('store_id',$store->id)->firstOrFail();
        $this->getJson('/api/products')->assertOk()->assertJsonCount(0);

        $this->actingAs($admin,'web')->post('/dashboard/products/'.$product->id.'/moderate',[
            'status'=>'active',
        ])->assertRedirect();
        $this->assertSame('active',$product->fresh()->status);
        $this->getJson('/api/products')->assertOk()->assertJsonCount(1);

        $campaignData=[
            'store_id'=>$store->id,'product_id'=>$product->id,'title'=>'Group Discount',
            'regular_price'=>100,'group_price'=>70,'min_participants'=>2,
            'target_participants'=>5,'start_at'=>now()->subMinute()->toDateTimeString(),
            'end_at'=>now()->addDay()->toDateTimeString(),
        ];
        $this->actingAs($seller,'sanctum')->postJson('/api/campaigns',$campaignData)
            ->assertCreated();
        $campaign=Campaign::where('product_id',$product->id)->firstOrFail();
        $this->getJson('/api/campaigns')->assertOk()->assertJsonCount(1);

        $buyerIds=[];
        foreach ([1,2] as $n) {
            $data=['name'=>'Buyer '.$n,'email'=>'buyer'.$n.'@journey.test',
                'phone'=>'0101000000'.$n,'password'=>'secret1234'];
            $response=$this->postJson('/api/register',$data)->assertCreated();
            $buyer=User::where('email',$data['email'])->firstOrFail();
            $buyerIds[]=$buyer->id;
            $token=$response->json('token');
            $this->withToken($token)->postJson('/api/campaigns/'.$campaign->id.'/join',[
                'quantity'=>$n,'idempotency_key'=>'buyer-'.$n.'-campaign-001',
            ])->assertOk();
            $this->withToken($token)->postJson('/api/addresses',[
                'title'=>'Home','city'=>'Cairo','area'=>'Dokki','phone'=>'01010000001',
            ])->assertCreated();
        }
        $this->assertSame(2,(int)$campaign->fresh()->current_participants);
        $this->actingAs($seller,'sanctum')
            ->postJson('/api/campaigns/'.$campaign->id.'/confirm')->assertOk();
        $this->assertSame('fulfilled',$campaign->fresh()->status);

        foreach ($buyerIds as $id) {
            $buyer=User::findOrFail($id);
            $addressId=\App\Models\Address::where('user_id',$id)->value('id');
            $this->actingAs($buyer,'sanctum')->postJson('/api/orders',[
                'campaign_id'=>$campaign->id,'address_id'=>$addressId,
                'payment_method'=>'cash_on_delivery',
            ])->assertCreated();
        }
        $this->assertSame(2,Order::count());
        $this->assertSame(2,(int)$product->fresh()->stock);
        $this->actingAs($admin,'sanctum')->getJson('/api/admin/overview')
            ->assertOk()->assertJsonPath('revenue',0);
        $first=Order::where('user_id',$buyerIds[0])->firstOrFail();
        $url='/api/orders/'.$first->id.'/transition';
        $this->actingAs($seller,'sanctum')->postJson($url,['action'=>'process'])->assertOk();
        $this->postJson($url,['action'=>'ship','delivery_reference'=>'TRACK-001'])->assertOk();
        $this->postJson($url,['action'=>'deliver'])->assertOk();
        $this->actingAs($admin,'sanctum')
            ->postJson('/api/admin/orders/'.$first->id.'/collect-cod')->assertOk();
        $this->getJson('/api/admin/overview')->assertOk()->assertJsonPath('revenue',70);
        $this->assertDatabaseCount('order_events',4);
        $this->assertDatabaseCount('catalog_moderation_events',2);
    }

    public function test_unapproved_product_cannot_be_used_to_publish_a_campaign(): void
    {
        $vendor=User::create([
            'name'=>'Vendor','email'=>'vendor@journey.test',
            'password'=>bcrypt('secret1234'),'role'=>'vendor','status'=>'active',
        ]);
        $store=Store::create([
            'user_id'=>$vendor->id,'name'=>'Shop','slug'=>'shop','status'=>'active',
        ]);
        $product=Product::create([
            'store_id'=>$store->id,'title'=>'Draft','slug'=>'draft',
            'regular_price'=>100,'stock'=>10,'status'=>'draft',
        ]);
        $this->actingAs($vendor,'sanctum')->postJson('/api/campaigns',[
            'store_id'=>$store->id,'product_id'=>$product->id,'title'=>'Premature',
            'regular_price'=>100,'group_price'=>70,'min_participants'=>2,
            'target_participants'=>5,'start_at'=>now()->toDateTimeString(),
            'end_at'=>now()->addDay()->toDateTimeString(),
        ])->assertUnprocessable();
        $this->assertDatabaseCount('campaigns',0);
    }
}
