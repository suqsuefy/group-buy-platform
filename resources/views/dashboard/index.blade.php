<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>لوحة جمعني</title>
<style>
*{box-sizing:border-box}body{font-family:Tahoma,Arial,sans-serif;background:#f4f7fc;color:#15223a;margin:0;line-height:1.6}header{background:#17377f;color:white;padding:20px 5%;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}h1{margin:0;font-size:24px}main{max-width:1280px;margin:28px auto;padding:0 18px}h2{font-size:21px;color:#193979;margin:28px 0 12px}.metrics{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px}.metric{background:white;border-radius:14px;padding:18px;box-shadow:0 2px 12px #15223a0a}.metric strong{display:block;font-size:23px;color:#193979}.panel{background:white;border-radius:14px;overflow-x:auto;box-shadow:0 2px 12px #15223a0a}table{border-collapse:collapse;width:100%;min-width:740px}th,td{text-align:right;padding:12px;border-bottom:1px solid #e9edf5;vertical-align:top}th{background:#eaf0fd;color:#193979}small,.muted{color:#63728d}button{border:0;background:#244fa5;color:white;border-radius:7px;padding:8px 12px;cursor:pointer;font:inherit}.secondary{background:#59687f}.warning{background:#9b4d19}.success{background:#117452}.signout{background:#fff;color:#17377f}.action{display:inline-flex;align-items:center;gap:6px;margin:2px 0}input{padding:7px;border:1px solid #cbd6e9;border-radius:7px;max-width:150px}.notice{background:#e7f4ee;color:#176345;padding:12px;border-radius:8px}.error{background:#fff0ef;color:#a12629;padding:12px;border-radius:8px}
</style>
</head>
<body>
<header><div><h1>جمعني — لوحة {{ $admin ? 'الإدارة' : 'البائع' }}</h1><small style="color:#d9e5ff">مرحبًا، {{ auth('web')->user()->name }}</small></div><form action="{{ route('dashboard.logout') }}" method="post">@csrf<button class="signout">تسجيل الخروج</button></form></header>
<main>
@if(session('notice'))<p class="notice" role="status">{{ session('notice') }}</p>@endif
@if(session('error'))<p class="error" role="alert">{{ session('error') }}</p>@endif
<section class="metrics">
<div class="metric"><span>إجمالي الطلبات</span><strong>{{ number_format($orderCount) }}</strong></div>
<div class="metric"><span>{{ $admin ? 'التحصيل المسجل' : 'تحصيل مسجل لطلبات متاجرك' }}</span><strong>{{ number_format($collected,2) }} ج.م</strong></div>
<div class="metric"><span>تم التسليم ولم يُسجّل تحصيلها</span><strong>{{ number_format($awaitingCollection,2) }} ج.م</strong></div>
<div class="metric"><span>المتاجر المعروضة</span><strong>{{ $stores->count() }}</strong></div>
</section>
<h2>الطلبات الحديثة</h2>
<div class="panel"><table><thead><tr><th>رقم الطلب</th><th>المتجر والعميل</th><th>القيمة</th><th>الحالة</th><th>إجراءات</th></tr></thead><tbody>
@forelse($orders as $order)
<tr><td>#{{ $order->id }}<div class="muted">{{ $order->created_at?->format('Y-m-d H:i') }}</div></td>
<td>{{ $order->store?->name ?? 'غير معروف' }}<div class="muted">{{ $order->user?->name ?? 'عميل غير معروف' }}</div></td>
<td>{{ number_format($order->total,2) }} ج.م</td><td>{{ ['pending'=>'معلق','processing'=>'تجهيز','shipped'=>'شحن','delivered'=>'تسليم','cancelled'=>'ملغى','paid'=>'مدفوع'][$order->status] ?? $order->status }}<div class="muted">{{ $order->cod_collected_at ? 'تحصيل مسجل' : 'لم يُسجل التحصيل' }}</div></td>
<td>
@if(in_array($order->status,['pending','processing','shipped']))
<form class="action" action="{{ route('dashboard.orders.transition',$order->id) }}" method="post">@csrf
@if($order->status==='pending')<input type="hidden" name="action" value="process"><button>بدء التجهيز</button>
@elseif($order->status==='processing')<input type="hidden" name="action" value="ship"><input name="delivery_reference" maxlength="120" required placeholder="مرجع الشحن"><button>تسجيل الشحن</button>
@elseif($order->status==='shipped')<input type="hidden" name="action" value="deliver"><button class="success">تأكيد التسليم</button>@endif
</form>
@endif
@if(in_array($order->status,['pending','processing']))
<form class="action" action="{{ route('dashboard.orders.transition',$order->id) }}" method="post">@csrf<input type="hidden" name="action" value="cancel"><button class="warning" onclick="return confirm('هل تريد إلغاء الطلب وإعادة المخزون؟')">إلغاء</button></form>
@endif
@if($admin && $order->status==='delivered' && !$order->cod_collected_at)
<form class="action" action="{{ route('dashboard.orders.collect',$order->id) }}" method="post">@csrf<button class="success" onclick="return confirm('هل تأكدت من تحصيل النقدية فعليًا؟')">تسجيل التحصيل</button></form>
@endif
</td></tr>
@empty<tr><td colspan="5">لا توجد طلبات حتى الآن.</td></tr>@endforelse
</tbody></table></div>
<h2>الحملات الحديثة</h2>
<div class="panel"><table><thead><tr><th>الحملة</th><th>المتجر</th><th>المشاركون</th><th>السعر الجماعي</th><th>الحالة</th></tr></thead><tbody>
@forelse($campaigns as $campaign)<tr><td>{{ $campaign->title }}</td><td>{{ $campaign->store?->name }}</td><td>{{ $campaign->current_participants }} / {{ $campaign->target_participants }}</td><td>{{ number_format($campaign->group_price,2) }} ج.م</td><td>{{ $campaign->status }}</td></tr>
@empty<tr><td colspan="5">لا توجد حملات.</td></tr>@endforelse
</tbody></table></div>
<h2>المتاجر</h2>
<div class="panel"><table><thead><tr><th>المتجر</th><th>المدينة</th><th>الحالة</th></tr></thead><tbody>
@forelse($stores as $store)<tr><td>{{ $store->name }}</td><td>{{ $store->city ?? '—' }}</td><td>{{ $store->status }}</td></tr>
@empty<tr><td colspan="3">لا توجد متاجر.</td></tr>@endforelse
</tbody></table></div>
<p class="muted">لوحة تجريبية تعرض بيانات النظام، وتسجيل التحصيل لا يغني عن التسوية مع شركة الشحن.</p>
</main></body></html>