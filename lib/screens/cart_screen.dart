import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/app_state.dart';

class CartScreen extends StatelessWidget {
  const CartScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final appState = context.watch<AppState>();

    return Scaffold(
      appBar: AppBar(title: const Text('السلة')),
      body: appState.cart.isEmpty
          ? const Center(child: Text('السلة فارغة'))
          : ListView.builder(
              itemCount: appState.cart.length,
              itemBuilder: (context, index) {
                final item = appState.cart[index];
                return ListTile(
                  title: Text(item['title']),
                  subtitle: Text('${item['price']} جنيه'),
                  trailing: IconButton(
                    onPressed: () => appState.removeFromCart(index),
                    icon: const Icon(Icons.delete),
                  ),
                );
              },
            ),
      bottomNavigationBar: appState.cart.isEmpty
          ? null
          : Padding(
              padding: const EdgeInsets.all(16),
              child: ElevatedButton(
                onPressed: () {
                  Navigator.pushNamed(context, '/checkout');
                },
                child: Text('إتمام الطلب (${appState.total} جنيه)'),
              ),
            ),
    );
  }
}
