import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:group_buying_app/services/api_endpoint.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test('rejects non-HTTPS and malformed API endpoints', () {
    expect(ApiEndpoint.isValidStagingUrl('https://staging.example.com/api'), isTrue);
    expect(ApiEndpoint.isValidStagingUrl('https://staging.example.com/api/'), isTrue);
    expect(ApiEndpoint.isValidStagingUrl('http://staging.example.com/api'), isFalse);
    expect(ApiEndpoint.isValidStagingUrl('https://staging.example.com/'), isFalse);
    expect(ApiEndpoint.isValidStagingUrl('https://user:secret@staging.example.com/api'), isFalse);
    expect(ApiEndpoint.isValidStagingUrl('https://staging.example.com/api?x=1'), isFalse);
  });

  test('persists staging endpoint and can reset it', () async {
    SharedPreferences.setMockInitialValues({});
    await ApiEndpoint.save('https://staging.example.com/api');
    expect(ApiEndpoint.baseUrl, 'https://staging.example.com/api');
    await ApiEndpoint.load();
    expect(ApiEndpoint.baseUrl, 'https://staging.example.com/api');
    await ApiEndpoint.reset();
    expect(ApiEndpoint.baseUrl, ApiEndpoint.bundledUrl);
  });
}
