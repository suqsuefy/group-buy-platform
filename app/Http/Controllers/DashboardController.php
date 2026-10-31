<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Order;
use App\Models\Store;
use App\Models\Product;
use App\Services\CatalogModerationService;
use App\Models\User;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    public function loginForm()
    {
        return view('dashboard.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        $key = 'dashboard-login:'.Str::lower($credentials['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'محاولات كثيرة. حاول لاحقًا.']);
        }
        if (!Auth::guard('web')->attempt([...$credentials, 'status' => 'active'])) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'بيانات الدخول غير صحيحة.']);
        }
        $user = Auth::guard('web')->user();
        if (!in_array($user->role, ['admin', 'vendor'], true)) {
            Auth::guard('web')->logout();
            throw ValidationException::withMessages(['email' => 'هذا الحساب غير مصرح له بدخول اللوحة.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        return redirect()->route('dashboard.home');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('dashboard.login');
    }

    public function index(Request $request)
    {
        $user = $request->user('web');
        $admin = $user->role === 'admin';
        $storeIds = $admin ? null : Store::where('user_id', $user->id)->pluck('id');
        $orders = Order::query()->with(['store','user','items.product']);
        $campaigns = Campaign::query()->with('store');
        if (!$admin) {
            $orders->whereIn('store_id', $storeIds);
            $campaigns->whereIn('store_id', $storeIds);
        }
        $scopedOrders = clone $orders;
        return view('dashboard.index', [
            'admin' => $admin,
            'orders' => $orders->latest()->limit(30)->get(),
            'campaigns' => $campaigns->latest()->limit(15)->get(),
            'stores' => $admin ? Store::latest()->limit(15)->get() : Store::where('user_id',$user->id)->get(),
            'products' => $admin ? Product::latest()->limit(30)->get() : Product::whereIn('store_id',$storeIds)->latest()->limit(30)->get(),
            'orderCount' => (clone $scopedOrders)->count(),
            'collected' => (clone $scopedOrders)->whereNotNull('cod_collected_at')->sum('total'),
            'awaitingCollection' => (clone $scopedOrders)->where('status','delivered')->whereNull('cod_collected_at')->sum('total'),
        ]);
    }

    public function transition(Request $request, int $order, OrderFulfillmentService $fulfillment)
    {
        $data = $request->validate([
            'action' => 'required|in:process,ship,deliver,cancel',
            'delivery_reference' => 'required_if:action,ship|nullable|string|max:120',
            'reason' => 'nullable|string|max:255',
        ]);
        $record = Order::with('store')->findOrFail($order);
        $user = $request->user('web');
        abort_unless($user->role === 'admin' || $record->store?->user_id === $user->id, 403);
        $result = $fulfillment->transition($order, $data['action'], $data['delivery_reference'] ?? null, $data['reason'] ?? null, $user->id);
        return back()->with($result['success'] ? 'notice' : 'error', $result['success'] ? 'تم تحديث الطلب.' : $result['message']);
    }

    public function collect(Request $request, int $order, OrderFulfillmentService $fulfillment)
    {
        abort_unless($request->user('web')->role === 'admin', 403);
        $result = $fulfillment->collectCod($order, $request->user('web')->id);
        return back()->with($result['success'] ? 'notice' : 'error', $result['success'] ? 'تم تسجيل التحصيل.' : $result['message']);
    }
    public function moderateStore(Request $request, int $store, CatalogModerationService $service)
    {
        abort_unless($request->user('web')->role === 'admin',403);
        $data=$request->validate(['status'=>'required|in:active,blocked']);
        $result=$service->setStoreStatus($store,$data['status'],$request->user('web')->id);
        return back()->with($result['success']?'notice':'error',$result['success']?'تم تحديث حالة المتجر.':$result['message']);
    }

    public function moderateProduct(Request $request, int $product, CatalogModerationService $service)
    {
        abort_unless($request->user('web')->role === 'admin',403);
        $data=$request->validate(['status'=>'required|in:active,inactive']);
        $result=$service->setProductStatus($product,$data['status'],$request->user('web')->id);
        return back()->with($result['success']?'notice':'error',$result['success']?'تم تحديث حالة المنتج.':$result['message']);
    }

}
