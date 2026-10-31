<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('campaign_participants',fn(Blueprint $t)=>$t->unique(['campaign_id','user_id'],'campaign_participant_unique'));
  Schema::table('orders',fn(Blueprint $t)=>$t->unique(['campaign_id','user_id'],'campaign_order_unique'));
 }
 public function down():void {
  Schema::table('orders',fn(Blueprint $t)=>$t->dropUnique('campaign_order_unique'));
  Schema::table('campaign_participants',fn(Blueprint $t)=>$t->dropUnique('campaign_participant_unique'));
 }
};
