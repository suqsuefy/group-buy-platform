<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role,string $suffix): User
    {
        return User::create([
            'name'=>$role.' '.$suffix,'email'=>$suffix.'@example.test',
            'phone'=>'01000000000','password'=>bcrypt('secret123'),
            'role'=>$role,'status'=>'active',
        ]);
    }

    public function test_guest_redirects_to_login_and_login_form_renders(): void
    {
        $this->get('/dashboard/login')->assertOk()->assertSee('جمعني');
        $this->get('/dashboard')->assertRedirect('/dashboard/login');
    }

    public function test_customers_cannot_access_admin_or_vendor_dashboard(): void
    {
        $customer=$this->user('customer','customer');
        $this->actingAs($customer,'web')->get('/dashboard')->assertForbidden();
    }

    public function test_vendor_only_sees_own_store_and_orders(): void
    {
        $vendor=$this->user('vendor','vendor');
        $other=$this->user('vendor','other');
        $first=Store::create(['user_id'=>$vendor->id,'name'=>'Vendor Store','slug'=>'vendor-store','status'=>'active']);
        Store::create(['user_id'=>$other->id,'name'=>'Hidden Other Store','slug'=>'hidden-store','status'=>'active']);
        Order::create(['user_id'=>$vendor->id,'store_id'=>$first->id,'total'=>100,'commission'=>10,'payment_method'=>'cash_on_delivery','status'=>'pending']);
        $this->actingAs($vendor,'web')->get('/dashboard')->assertOk()
            ->assertSee('Vendor Store')->assertDontSee('Hidden Other Store');
    }

    public function test_dashboard_login_accepts_vendor_but_rejects_customer(): void
    {
        $vendor=$this->user('vendor','vendor');
        $this->post('/dashboard/login',['email'=>$vendor->email,'password'=>'secret123'])
            ->assertRedirect('/dashboard');
        $this->post('/dashboard/logout')->assertRedirect('/dashboard/login');

        $customer=$this->user('customer','customer');
        $this->post('/dashboard/login',['email'=>$customer->email,'password'=>'secret123'])
            ->assertSessionHasErrors('email');
    }

    public function test_dashboard_admin_sees_all_stores_but_vendor_cannot_collect_cod(): void
    {
        $admin=$this->user('admin','admin');
        $vendor=$this->user('vendor','vendor');
        Store::create(['user_id'=>$vendor->id,'name'=>'Store X','slug'=>'store-x','status'=>'active']);
        $this->actingAs($admin,'web')->get('/dashboard')->assertOk()->assertSee('Store X');
        $this->actingAs($vendor,'web')->post('/dashboard/orders/1/collect')->assertForbidden();
    }
}
