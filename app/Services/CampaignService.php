<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignParticipant;

class CampaignService
{
    public function joinCampaign(int $campaignId, int $userId, int $quantity = 1): array
    {
        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->status !== 'active') {
            return [
                'success' => false,
                'message' => 'Campaign is not active',
            ];
        }

        $participant = CampaignParticipant::firstOrNew([
            'campaign_id' => $campaignId,
            'user_id' => $userId,
        ]);

        $participant->quantity = ($participant->exists ? $participant->quantity : 0) + $quantity;
        $participant->status = 'joined';
        $participant->save();

        $campaign->refresh();
        $campaign->current_participants = $campaign->participants()->count();
        $campaign->save();

        return [
            'success' => true,
            'message' => 'Joined campaign successfully',
            'campaign' => $campaign,
            'participant' => $participant,
        ];
    }

    public function confirmCampaign(int $campaignId): array
    {
        $campaign = Campaign::findOrFail($campaignId);
        $count = $campaign->participants()->count();

        if ($count >= $campaign->min_participants) {
            $campaign->status = 'fulfilled';
            $campaign->save();

            return [
                'success' => true,
                'message' => 'Campaign confirmed',
                'campaign' => $campaign,
            ];
        }

        return [
            'success' => false,
            'message' => 'Minimum participants not reached',
            'current_participants' => $count,
            'min_participants' => $campaign->min_participants,
        ];
    }
}
