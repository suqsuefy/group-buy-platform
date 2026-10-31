import 'package:flutter/material.dart';

import 'screens/auth_screen.dart';
import 'screens/home_screen.dart';
import 'screens/cart_screen.dart';
import 'screens/checkout_screen.dart';
import 'screens/campaign_detail_screen.dart';

class GroupBuyingApp extends StatelessWidget {
  const GroupBuyingApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Group Buying',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primarySwatch: Colors.indigo,
        fontFamily: 'Cairo',
      ),
      initialRoute: '/',
      routes: {
        '/': (context) => const AuthScreen(),
        '/home': (context) => const HomeScreen(),
        '/cart': (context) => const CartScreen(),
        '/checkout': (context) => const CheckoutScreen(),
      },
      onGenerateRoute: (settings) {
        if (settings.name == '/campaign') {
          final id = settings.arguments as int;
          return MaterialPageRoute(
            builder: (_) => CampaignDetailScreen(campaignId: id),
          );
        }
        return null;
      },
    );
  }
}
