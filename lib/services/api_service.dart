import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

class ApiService {
  ApiService()
      : _dio = Dio(BaseOptions(
          baseUrl: const String.fromEnvironment(
            'API_BASE_URL',
            defaultValue: 'http://10.0.2.2:8000/api',
          ),
          headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
          connectTimeout: const Duration(seconds: 20),
          receiveTimeout: const Duration(seconds: 20),
        ));
  final Dio _dio;

  Future<Map<String, dynamic>> register({
    required String name, required String email, required String phone, required String password,
  }) async {
    final r = await _dio.post('/register', data: {'name': name, 'email': email, 'phone': phone, 'password': password});
    return Map<String, dynamic>.from(r.data);
  }
  Future<Map<String, dynamic>> login({required String email, required String password}) async {
    final r = await _dio.post('/login', data: {'email': email, 'password': password});
    return Map<String, dynamic>.from(r.data);
  }
  Future<List<dynamic>> getCampaigns() async {
    final r = await _dio.get('/campaigns'); return List<dynamic>.from(r.data);
  }
  Future<Map<String, dynamic>> getCampaign(int id) async {
    final r = await _dio.get('/campaigns/$id'); return Map<String, dynamic>.from(r.data);
  }
  Future<Map<String, dynamic>> joinCampaign({required int id, required String token, int quantity = 1, required String idempotencyKey}) async {
    final r = await _dio.post('/campaigns/$id/join', data: {'quantity': quantity, 'idempotency_key': idempotencyKey},
      options: Options(headers: {'Authorization': 'Bearer $token'}));
    return Map<String, dynamic>.from(r.data);
  }
  Future<List<dynamic>> getAddresses(String token) async {
    final r = await _dio.get('/addresses', options: Options(headers: {'Authorization': 'Bearer $token'}));
    return List<dynamic>.from(r.data);
  }
  Future<Map<String, dynamic>> addAddress({
    required String token, required String title, required String city,
    required String area, required String phone, String? street,
  }) async {
    final r = await _dio.post('/addresses',
      data: {'title': title, 'city': city, 'area': area, 'phone': phone, 'street': street},
      options: Options(headers: {'Authorization': 'Bearer $token'}));
    return Map<String, dynamic>.from(r.data);
  }
  Future<Map<String, dynamic>> createOrder({
    required int campaignId, required int addressId, required String token,
  }) async {
    final r = await _dio.post('/orders', data: {
      'campaign_id': campaignId, 'address_id': addressId, 'payment_method': 'cash_on_delivery',
    }, options: Options(headers: {'Authorization': 'Bearer $token'}));
    return Map<String, dynamic>.from(r.data);
  }
  Future<void> logout(String token) async {
    try { await _dio.post('/logout', options: Options(headers: {'Authorization': 'Bearer $token'})); }
    catch (e) { debugPrint('Logout request failed: $e'); }
  }
}
