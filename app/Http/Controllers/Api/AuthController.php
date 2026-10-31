<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller {
 public function register(Request $request) {
  $d=$request->validate(['name'=>'required|string|max:255','email'=>'required|email|unique:users,email','phone'=>'required|string|max:30','password'=>'required|string|min:8','city'=>'nullable|string|max:100']);
  $u=User::create(['name'=>$d['name'],'email'=>$d['email'],'phone'=>$d['phone'],'city'=>$d['city']??null,'password'=>Hash::make($d['password']),'role'=>'customer','status'=>'active']);
  return response()->json(['token'=>$u->createToken('app')->plainTextToken,'user'=>$u],201);
 }
 public function login(Request $request) {
  $d=$request->validate(['email'=>'required|email','password'=>'required|string']);
  $u=User::where('email',$d['email'])->first();
  if(!$u || !Hash::check($d['password'],$u->password)) return response()->json(['message'=>'Invalid credentials'],401);
  if($u->status!=='active') return response()->json(['message'=>'Account blocked'],403);
  return response()->json(['token'=>$u->createToken('app')->plainTextToken,'user'=>$u]);
 }
 public function logout(Request $request) { $request->user()->currentAccessToken()->delete();return response()->json(['message'=>'Logged out']); }
}
