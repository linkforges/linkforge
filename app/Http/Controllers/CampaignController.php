<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    /** Selectable dot colours (no purple/pink, per brand). */
    public const COLORS = ['emerald', 'blue', 'amber', 'teal', 'orange', 'slate'];

    public function index(Request $request)
    {
        $campaigns = $request->user()->campaigns()
            ->withCount('links')
            ->withSum('links as clicks_sum', 'clicks')
            ->latest()
            ->get();

        return view('campaigns.index', ['campaigns' => $campaigns, 'colors' => self::COLORS]);
    }

    public function store(Request $request)
    {
        $data = $this->validateCampaign($request);
        $request->user()->campaigns()->create($data);

        return back()->with('status', 'Campaign created.');
    }

    public function update(Request $request, Campaign $campaign)
    {
        abort_unless((int) $campaign->user_id === (int) $request->user()->id, 403);
        $campaign->update($this->validateCampaign($request));

        return back()->with('status', 'Campaign updated.');
    }

    public function destroy(Request $request, Campaign $campaign)
    {
        abort_unless((int) $campaign->user_id === (int) $request->user()->id, 403);

        // Keep the links; just unassign them from the deleted campaign.
        $campaign->links()->update(['campaign_id' => null]);
        $campaign->delete();

        return back()->with('status', 'Campaign deleted. Its links were kept.');
    }

    /** @return array<string,mixed> */
    private function validateCampaign(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['nullable', Rule::in(self::COLORS)],
        ]);
    }
}
