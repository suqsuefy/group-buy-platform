import 'package:flutter_test/flutter_test.dart';
import 'package:group_buying_app/providers/app_state.dart';

void main() {
  test('session logout clears token and cart', () {
    final state = AppState();
    state.login('token-1');
    state.addToCart({
      'campaign_id': 12, 'id': 10, 'title': 'Item',
      'price': '70.00', 'qty': 2,
    });
    expect(state.isLoggedIn, isTrue);
    expect(state.total, 140);
    state.logout();
    expect(state.isLoggedIn, isFalse);
    expect(state.token, isEmpty);
    expect(state.cart, isEmpty);
  });

  test('one campaign has one cart entry and expected total', () {
    final state = AppState();
    state.addToCart({'campaign_id': 3, 'price': 50, 'qty': 1});
    state.addToCart({'campaign_id': 3, 'price': 50, 'qty': 3});
    expect(state.cart.length, 1);
    expect(state.total, 150);
  });
}
