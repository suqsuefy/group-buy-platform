import 'package:flutter/material.dart';

class AppState extends ChangeNotifier {
  String token = '';
  bool isLoggedIn = false;

  final List<Map<String, dynamic>> cart = [];

  void login(String newToken) {
    token = newToken;
    isLoggedIn = true;
    notifyListeners();
  }

  void logout() {
    token = '';
    isLoggedIn = false;
    cart.clear();
    notifyListeners();
  }

  void addToCart(Map<String, dynamic> item) {
    cart.add(item);
    notifyListeners();
  }

  void removeFromCart(int index) {
    cart.removeAt(index);
    notifyListeners();
  }

  double get total {
    double sum = 0;
    for (final item in cart) {
      sum += (item['price'] as num).toDouble() * (item['qty'] as int);
    }
    return sum;
  }
}
