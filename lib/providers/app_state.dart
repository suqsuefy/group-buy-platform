import 'package:flutter/material.dart';
class AppState extends ChangeNotifier {
  String token = '';
  bool isLoggedIn = false;
  final List<Map<String, dynamic>> cart = [];
  void login(String newToken) { token=newToken;isLoggedIn=true;notifyListeners(); }
  void logout() { token='';isLoggedIn=false;cart.clear();notifyListeners(); }
  void addToCart(Map<String,dynamic> item) {
    final campaignId=item['campaign_id'];
    cart.removeWhere((x)=>x['campaign_id']==campaignId);
    cart.add(item);
    notifyListeners();
  }
  void removeFromCart(int index){ cart.removeAt(index);notifyListeners(); }
  void clearCart(){cart.clear();notifyListeners();}
  double get total {
    var sum=0.0;
    for(final item in cart){sum+=(num.parse(item['price'].toString())).toDouble()*(item['qty'] as int);}
    return sum;
  }
}
