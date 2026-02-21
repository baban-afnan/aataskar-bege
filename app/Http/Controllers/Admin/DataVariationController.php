<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataVariation;
use Illuminate\Http\Request;

class DataVariationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Define the standard services based on service_id as requested
        $availableServices = [
            'mtn-data' => ['name' => 'MTN Data', 'icon' => 'ti ti-device-mobile', 'color' => 'warning'],
            'airtel-data' => ['name' => 'Airtel Data', 'icon' => 'ti ti-device-mobile', 'color' => 'danger'],
            'glo-data' => ['name' => 'Glo Data', 'icon' => 'ti ti-device-mobile', 'color' => 'success'],
            'etisalat-data' => ['name' => '9mobile Data', 'icon' => 'ti ti-device-mobile', 'color' => 'dark'],
            'waec' => ['name' => 'WAEC PIN', 'icon' => 'ti ti-school', 'color' => 'secondary'],
            'smile-direct' => ['name' => 'Smile Direct', 'icon' => 'ti ti-wifi', 'color' => 'info'],
            'dstv' => ['name' => 'DStv Subscription', 'icon' => 'ti ti-device-tv-old', 'color' => 'primary'],
            'gotv' => ['name' => 'GOtv Subscription', 'icon' => 'ti ti-device-tv-old', 'color' => 'primary'],
            'startimes' => ['name' => 'StarTimes Subscription', 'icon' => 'ti ti-device-tv-old', 'color' => 'primary'],
            'showmax' => ['name' => 'Showmax', 'icon' => 'ti ti-brand-netflix', 'color' => 'danger'],
        ];

        // Get counts for each service using service_id
        $serviceCounts = DataVariation::select('service_id', \DB::raw('count(*) as total'))
            ->groupBy('service_id')
            ->pluck('total', 'service_id')
            ->toArray();

        // Get active counts for status determination
        $activeServiceCounts = DataVariation::where('status', true)
            ->select('service_id', \DB::raw('count(*) as total'))
            ->groupBy('service_id')
            ->pluck('total', 'service_id')
            ->toArray();

        // Stats for the index page
        $totalVariationsCount = DataVariation::count();
        $activeVariationsCount = DataVariation::where('status', true)->count();
        $inactiveVariationsCount = DataVariation::where('status', false)->count();

        return view('admin.data-variations.index', compact(
            'availableServices', 
            'serviceCounts', 
            'activeServiceCounts',
            'totalVariationsCount', 
            'activeVariationsCount', 
            'inactiveVariationsCount'
        ));
    }

    /**
     * Display the specified resource.
     */
    public function show($serviceId)
    {
        $services = [
            'mtn-data' => 'MTN Data',
            'airtel-data' => 'Airtel Data',
            'glo-data' => 'Glo Data',
            'etisalat-data' => '9mobile Data',
            'waec' => 'WAEC PIN',
            'smile-direct' => 'Smile Direct',
            'dstv' => 'DStv Subscription',
            'gotv' => 'GOtv Subscription',
            'startimes' => 'StarTimes Subscription',
            'showmax' => 'Showmax',
        ];

        if (!isset($services[$serviceId])) {
            return redirect()->route('admin.data-variations.index')->with('error', 'Invalid service specified.');
        }

        $serviceName = $services[$serviceId];
        $variations = DataVariation::where('service_id', $serviceId)
            ->latest()
            ->paginate(15);

        return view('admin.data-variations.show', compact('variations', 'serviceId', 'serviceName'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|string',
            'name' => 'required|string|max:255',
            'variation_amount' => 'required|numeric|min:0',
            'variation_code' => 'required|string|unique:data_variations,variation_code',
            'convinience_fee' => 'nullable|numeric|min:0',
        ]);

        $validated['convinience_fee'] = $validated['convinience_fee'] ?? 0;
        $validated['status'] = $request->has('status');
        $validated['fixedPrice'] = $request->has('fixedPrice');

        DataVariation::create($validated);

        return back()->with('success', 'Variation added successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DataVariation $dataVariation)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'variation_amount' => 'required|numeric|min:0',
            'variation_code' => 'required|string|unique:data_variations,variation_code,' . $dataVariation->id,
            'convinience_fee' => 'nullable|numeric|min:0',
            'service_id' => 'nullable|string',
        ]);

        $validated['convinience_fee'] = $validated['convinience_fee'] ?? 0;
        $validated['status'] = $request->has('status');
        $validated['fixedPrice'] = $request->has('fixedPrice');

        $dataVariation->update($validated);

        return back()->with('success', 'Variation updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DataVariation $dataVariation)
    {
        $dataVariation->delete();
        return back()->with('success', 'Variation deleted successfully.');
    }
}
