<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    protected CampaignService $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    public function index()
    {
        return response()->json(
            Campaign::with(['store', 'product'])
                ->where('status', 'active')
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function show($id)
    {
        $campaign = Campaign::with(['store', 'product', 'participants.user'])->findOrFail($id);

        return response()->json($campaign);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'product_id' => 'required|exists:products,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'regular_price' => 'required|numeric',
            'group_price' => 'required|numeric',
            'min_participants' => 'required|integer|min:1',
            'target_participants' => 'required|integer|min:1',
            'start_at' => 'required|date',
            'end_at' => 'required|date|after:start_at',
            'city' => 'nullable|string',
        ]);

        $campaign = Campaign::create([
            ...$data,
            'current_participants' => 0,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Campaign created successfully',
            'campaign' => $campaign,
        ], 201);
    }

    public function join(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'nullable|integer|min:1',
        ]);

        $result = $this->campaignService->joinCampaign(
            (int) $id,
            Auth::id(),
            (int) $request->input('quantity', 1)
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    public function confirm($id)
    {
        $result = $this->campaignService->confirmCampaign((int) $id);

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
