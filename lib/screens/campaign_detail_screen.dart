import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/app_state.dart';
import '../services/api_service.dart';

class CampaignDetailScreen extends StatefulWidget {
  final int campaignId;

  const CampaignDetailScreen({super.key, required this.campaignId});

  @override
  State<CampaignDetailScreen> createState() => _CampaignDetailScreenState();
}

class _CampaignDetailScreenState extends State<CampaignDetailScreen> {
  final ApiService _apiService = ApiService();
  Map<String, dynamic> campaign = {};
  bool isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadCampaign();
  }

  Future<void> _loadCampaign() async {
    try {
      final data = await _apiService.getCampaign(widget.campaignId);
      if (!mounted) return;
      setState(() {
        campaign = data;
        isLoading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final appState = context.read<AppState>();

    if (isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    return Scaffold(
      appBar: AppBar(title: Text(campaign['title'] ?? 'الحملة')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Image.network(
              'https://images.unsplash.com/photo-1585386959984-a4155229c9b2',
              height: 220,
              width: double.infinity,
              fit: BoxFit.cover,
            ),
            const SizedBox(height: 16),
            Text(
              campaign['title'] ?? '',
              style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),
            Text(
              campaign['description'] ?? '',
              style: const TextStyle(fontSize: 16),
            ),
            const SizedBox(height: 18),
            Row(
              children: [
                const Text('السعر العادي: '),
                Text(
                  '${campaign['regular_price']} جنيه',
                  style: const TextStyle(decoration: TextDecoration.lineThrough),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                const Text('السعر الجماعي: '),
                Text(
                  '${campaign['group_price']} جنيه',
                  style: const TextStyle(
                    color: Colors.indigo,
                    fontWeight: FontWeight.bold,
                    fontSize: 22,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 18),
            Text(
              'المشاركون الحاليون: ${campaign['current_participants'] ?? 0} من ${campaign['target_participants'] ?? 0}',
            ),
            const SizedBox(height: 20),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () async {
                  try {
                    final result = await _apiService.joinCampaign(
                      id: widget.campaignId,
                      token: appState.token,
                      quantity: 1,
                    );

                    if (!mounted) return;

                    appState.addToCart({
                      'id': campaign['product_id'],
                      'campaign_id': widget.campaignId,
                      'title': campaign['title'],
                      'price': campaign['group_price'],
                      'qty': 1,
                    });

                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text(result['message'] ?? 'تم الانضمام')),
                    );
                  } catch (e) {
                    if (!mounted) return;
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(content: Text('حدث خطأ: $e')),
                    );
                  }
                },
                child: const Text('انضم للحملة'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
