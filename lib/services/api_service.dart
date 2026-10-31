import 'package:dio/dio.dart';

class ApiService {
  final Dio _dio = Dio(
    BaseOptions(
      baseUrl: 'http://localhost:8000/api',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      connectTimeout: const Duration(seconds: 20),
      receiveTimeout: const Duration(seconds: 20),
    ),
  );

  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String phone,
    required String password,
  }) async {
    final response = await _dio.post('/register', data: {
      'name': name,
      'email': email,
      'phone': phone,
      'password': password,
    });

    return response.data;
  }

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final response = await _dio.post('/login', data: {
      'email': email,
      'password': password,
    });

    return response.data;
  }

  Future<List<dynamic>> getCampaigns() async {
    final response = await _dio.get('/campaigns');
    return response.data;
  }

  Future<Map<String, dynamic>> getCampaign(int id) async {
    final response = await _dio.get('/campaigns/$id');
    return response.data;
  }

  Future<Map<String, dynamic>> joinCampaign({
    required int id,
    required String token,
    int quantity = 1,
  }) async {
    final response = await _dio.post(
      '/campaigns/$id/join',
      data: {'quantity': quantity},
      options: Options(
        headers: {
          'Authorization': 'Bearer $token',
        },
      ),
    );

    return response.data;
  }

  Future<Map<String, dynamic>> createOrder({
    required List<Map<String, dynamic>> items,
    required String paymentMethod,
    required String token,
  }) async {
    final response = await _dio.post(
      '/orders',
      data: {
        'items': items,
        'payment_method': paymentMethod,
      },
      options: Options(
        headers: {
          'Authorization': 'Bearer $token',
        },
      ),
    );

    return response.data;
  }
}
