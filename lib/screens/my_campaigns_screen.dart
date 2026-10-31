import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/app_state.dart';
import '../services/api_service.dart';

class MyCampaignsScreen extends StatefulWidget {
  const MyCampaignsScreen({super.key});
  @override State<MyCampaignsScreen> createState() => _MyCampaignsScreenState();
}
class _MyCampaignsScreenState extends State<MyCampaignsScreen> {
  final api = ApiService();
  bool loading = true;
  String? error;
  List<dynamic> entries = [];
  @override void initState() { super.initState(); load(); }
  Future<void> load() async {
    if (!context.read<AppState>().isLoggedIn) {
      setState(() { loading = false; error = 'يرجى تسجيل الدخول أولًا'; });
      return;
    }
    try {
      final data = await api.getMyCampaigns(context.read<AppState>().token);
      if (mounted) setState(() { entries = data; loading = false; error = null; });
    } catch (_) {
      if (mounted) setState(() { loading = false; error = 'تعذر تحميل مشاركاتك'; });
    }
  }
  @override Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('مشاركاتي في الحملات')),
      body: loading ? const Center(child: CircularProgressIndicator()) :
      error != null ? Center(child: Text(error!)) :
      RefreshIndicator(onRefresh: load, child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        itemCount: entries.length,
        itemBuilder: (context, index) {
          final entry = Map<String,dynamic>.from(entries[index] as Map);
          final campaign = Map<String,dynamic>.from(entry['campaign'] as Map);
          final order = entry['order'];
          final confirmed = campaign['status'] == 'fulfilled';
          final canCheckout = confirmed && order == null;
          return Card(margin: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            child: Padding(padding: const EdgeInsets.all(14),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('${campaign['title']}', style: Theme.of(context).textTheme.titleMedium),
                Text('الكمية المحجوزة: ${entry['quantity']}'),
                Text(confirmed ? 'تم تأكيد الحملة' : 'حالة الحملة: ${campaign['status']}'),
                if (order != null) Text('طلب مسجل #${order['id']} — ${order['status']}'),
                if (canCheckout) ElevatedButton(onPressed: () {
                  final state = context.read<AppState>();
                  state.addToCart({
                    'id': campaign['product_id'], 'campaign_id': campaign['id'],
                    'title': campaign['title'], 'price': campaign['group_price'],
                    'qty': entry['quantity'],
                  });
                  Navigator.pushNamed(context, '/checkout');
                }, child: const Text('إتمام الشراء بعد تأكيد الحملة')),
              ]),
            ),
          );
        },
      )),
    );
  }
}
