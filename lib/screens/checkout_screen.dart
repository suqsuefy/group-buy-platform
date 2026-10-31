import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/app_state.dart';
import '../services/api_service.dart';

class CheckoutScreen extends StatefulWidget {
  const CheckoutScreen({super.key});

  @override
  State<CheckoutScreen> createState() => _CheckoutScreenState();
}

class _CheckoutScreenState extends State<CheckoutScreen> {
  final ApiService _apiService = ApiService();
  String paymentMethod = 'cash_on_delivery';

  @override
  Widget build(BuildContext context) {
    final appState = context.watch<AppState>();

    return Scaffold(
      appBar: AppBar(title: const Text('إتمام الطلب')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            DropdownButtonFormField<String>(
              value: paymentMethod,
              items: const [
                DropdownMenuItem(
                  value: 'cash_on_delivery',
                  child: Text('الدفع عند الاستلام'),
                ),
                DropdownMenuItem(
                  value: 'card',
                  child: Text('بطاقة'),
                ),
              ],
              onChanged: (value) {
                setState(() {
                  paymentMethod = value ?? 'cash_on_delivery';
                });
              },
            ),
            const SizedBox(height: 18),
            Text(
              'الإجمالي: ${appState.total} جنيه',
              style: const TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 18),
            ElevatedButton(
              onPressed: () async {
                final items = appState.cart.map((item) {
                  return {
                    'product_id': item['id'],
                    'quantity': item['qty'],
                  };
                }).toList();

                try {
                  final result = await _apiService.createOrder(
                    items: List<Map<String, dynamic>>.from(items),
                    paymentMethod: paymentMethod,
                    token: appState.token,
                  );

                  if (!mounted) return;

                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text(result['message'] ?? 'تم إنشاء الطلب')),
                  );

                  appState.cart.clear();
                  Navigator.pushNamedAndRemoveUntil(context, '/home', (route) => false);
                } catch (e) {
                  if (!mounted) return;
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text('حدث خطأ: $e')),
                  );
                }
              },
              child: const Text('تأكيد الطلب'),
            ),
          ],
        ),
      ),
    );
  }
}
