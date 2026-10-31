<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ApiAccessTest extends TestCase {
 use RefreshDatabase;
 public function test_guest_can_view_campaigns():void {
  $this->getJson('/api/campaigns')->assertOk();
 }
 public function test_guest_cannot_create_campaign():void {
  $this->postJson('/api/campaigns',[])->assertUnauthorized();
 }
 public function test_guest_cannot_view_admin():void {
  $this->getJson('/api/admin/overview')->assertUnauthorized();
 }
 public function test_customer_cannot_view_admin():void {
  $user=User::create(['name'=>'Test','email'=>'test@example.com','phone'=>'01000000000','password'=>bcrypt('password123'),'role'=>'customer','status'=>'active']);
  $this->actingAs($user,'sanctum')->getJson('/api/admin/overview')->assertForbidden();
 }
}
