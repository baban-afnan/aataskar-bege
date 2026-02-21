<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Services1;
use App\Models\ServiceField;
use App\Models\ServicePrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Services1::withCount(['fields', 'prices']);

        // Filter by Status
        if ($request->has('status')) {
            if ($request->status == 'active') {
                $query->where('is_active', true);
            } elseif ($request->status == 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        if ($request->sort == 'oldest') {
            $query->oldest();
        } else {
            $query->latest();
        }

        $services = $query->paginate(10);

        // Stats
        $totalServicesCount = Services1::count();
        $activeServicesCount = Services1::where('is_active', true)->count();
        $inactiveServicesCount = Services1::where('is_active', false)->count();

        return view('services.index', compact('services', 'totalServicesCount', 'activeServicesCount', 'inactiveServicesCount'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $validated['is_active'] = $request->has('is_active');

        if ($request->hasFile('image')) {
            // Store new image
            $path = $request->file('image')->store('services', 'public');
            $validated['image'] = config('app.url') . '/storage/' . $path;
        }

        Services1::create($validated);

        return back()->with('success', 'Service created successfully.');
    }

    public function update(Request $request, Services1 $service)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $validated['is_active'] = $request->has('is_active');

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($service->image) {
                $oldPath = str_replace(config('app.url') . '/storage/', '', $service->image);
                Storage::disk('public')->delete($oldPath);
            }

            // Store new image
            $path = $request->file('image')->store('services', 'public');

            // Save full URL
            $validated['image'] = config('app.url') . '/storage/' . $path;
        }

        $service->update($validated);

        return back()->with('success', 'Service updated successfully.');
    }

    public function destroy(Services1 $service)
    {
        // Delete image from storage if exists
        if ($service->image) {
            $oldPath = str_replace(config('app.url') . '/storage/', '', $service->image);
            Storage::disk('public')->delete($oldPath);
        }

        $service->delete();
        return back()->with('success', 'Service deleted successfully.');
    }

    public function show(Services1 $service)
    {
        $fields = $service->fields()->paginate(10, ['*'], 'fields_page');
        $prices = $service->prices()->with('field')->paginate(10, ['*'], 'prices_page');

        return view('services.show', compact('service', 'fields', 'prices'));
    }

    // Field Management
    public function storeField(Request $request, Services1 $service)
    {
        // Custom Check for Unique Field Code
        if (ServiceField::where('field_code', $request->field_code)->exists()) {
            $lastField = ServiceField::latest('id')->first();
            $lastCode = $lastField ? $lastField->field_code : 'None';
            return back()->with('error', "Field code already exists. The last field code used was: {$lastCode}")->withInput();
        }

        $validated = $request->validate([
            'field_name' => 'required|string|max:255',
            'field_code' => 'required|string|max:255', // Removed unique check from database level to handle manually? Actually better to double check but the prompt asked for "if exists return error message..."
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $service->fields()->create($validated);

        return back()->with('success', 'Service field added successfully.');
    }

    public function updateField(Request $request, ServiceField $field)
    {
        // Check uniqueness if changed
        if ($request->field_code !== $field->field_code && ServiceField::where('field_code', $request->field_code)->exists()) {
             $lastField = ServiceField::latest('id')->first();
             $lastCode = $lastField ? $lastField->field_code : 'None';
             return back()->with('error', "Field code already exists. The last field code used was: {$lastCode}")->withInput();
        }

        $validated = $request->validate([
            'field_name' => 'required|string|max:255',
            'field_code' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $field->update($validated);

        return back()->with('success', 'Service field updated successfully.');
    }

    public function destroyField(ServiceField $field)
    {
        try {
            $field->delete();
            return back()->with('success', 'Service field deleted successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            // Check for integrity constraint violation (SQLState 23000)
            if ($e->getCode() === "23000") {
                return back()->with('error', 'Cannot delete this field because it is linked to existing user records or transactions. Please deactivate it instead to preserve data integrity.');
            }
            // Rethrow other errors or handle generic failure
            return back()->with('error', 'An error occurred while attempting to delete the field: ' . $e->getMessage());
        }
    }

    // Price Management
    public function storePrice(Request $request, Services1 $service)
    {
        $validated = $request->validate([
            'service_fields_id' => 'nullable|exists:service_fields,id',
            'user_type' => 'required|in:personal,agent,partner,business,staff,api',
            'price' => 'required|numeric|min:0',
        ]);

        $service->prices()->create($validated);

        return back()->with('success', 'Price configuration added successfully.');
    }

    public function updatePrice(Request $request, ServicePrice $price)
    {
        $validated = $request->validate([
            'user_type' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $price->update($validated);

        return back()->with('success', 'Price configuration updated successfully.');
    }

    public function destroyPrice(ServicePrice $price)
    {
        $price->delete();
        return back()->with('success', 'Price configuration deleted successfully.');
    }
}
