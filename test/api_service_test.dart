import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:group_buying_app/services/api_endpoint.dart';
import 'package:group_buying_app/services/api_service.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test('existing API client uses changed staging server for login', () async {
    SharedPreferences.setMockInitialValues({});
    await ApiEndpoint.reset();

    String? seenBaseUrl;
    final client = Dio();
    client.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) {
        seenBaseUrl = options.baseUrl;
        handler.resolve(Response(
          requestOptions: options,
          statusCode: 200,
          data: {'token': 'test-token'},
        ));
      },
    ));
    final api = ApiService(client: client);

    await ApiEndpoint.save('https://staging.example.com/api');
    final response = await api.login(email: 'test@example.com', password: 'test');
    expect(seenBaseUrl, 'https://staging.example.com/api');
    expect(response['token'], 'test-token');
    await ApiEndpoint.reset();
  });
}
