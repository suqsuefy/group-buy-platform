import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';

class FirebaseService {
  static Future<void> init() async {
    const enabled = bool.fromEnvironment('ENABLE_FIREBASE', defaultValue: false);
    if (!enabled) return; // Activate only after platform configuration is installed.
    try {
      await Firebase.initializeApp();
      final messaging = FirebaseMessaging.instance;
      final settings = await messaging.requestPermission();
      if (settings.authorizationStatus == AuthorizationStatus.authorized) {
        await messaging.getToken();
      }
    } catch (e) {
      debugPrint('Firebase setup unavailable: $e');
    }
  }
}
