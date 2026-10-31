import 'package:shared_preferences/shared_preferences.dart';

/// Test-only server configuration. Do not store authentication tokens here.
class ApiEndpoint {
  static const bundledUrl = String.fromEnvironment(
    'API_BASE_URL', defaultValue: 'http://10.0.2.2:8000/api');
  static const _prefKey = 'staging_api_endpoint';
  static String? _customUrl;

  static String get baseUrl => _customUrl ?? bundledUrl;

  static bool isValidStagingUrl(String candidate) {
    final uri = Uri.tryParse(candidate.trim());
    return uri != null &&
        uri.scheme == 'https' &&
        uri.host.isNotEmpty &&
        uri.userInfo.isEmpty &&
        uri.query.isEmpty &&
        uri.fragment.isEmpty &&
        uri.pathSegments.where((part) => part.isNotEmpty).lastOrNull == 'api';
  }

  static Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    final value = prefs.getString(_prefKey);
    _customUrl = value != null && isValidStagingUrl(value) ? value : null;
  }

  static Future<void> save(String url) async {
    final value = url.trim().replaceFirst(RegExp(r'/$'), '');
    if (!isValidStagingUrl(value)) throw ArgumentError('HTTPS API URL ending with /api required');
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefKey, value);
    _customUrl = value;
  }

  static Future<void> reset() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_prefKey);
    _customUrl = null;
  }
}
