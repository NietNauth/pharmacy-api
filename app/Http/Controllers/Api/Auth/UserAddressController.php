<?php
/*
 * File: app/Http/Controllers/Api/Auth/UserAddressController.php
 */

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserAddressController extends Controller
{
    /**
     * Get user's addresses
     */
    public function index()
    {
        $addresses = Auth::user()->addresses;
        return response()->json([
            'success' => true,
            'data' => $addresses
        ]);
    }

    /**
     * Store a new address
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'label' => 'required|string|max:50',
            'recipient_name' => 'nullable|string|max:150',
            'recipient_phone' => 'nullable|string|max:20',
            'address_line' => 'required|string',
            'district' => 'nullable|string',
            'city' => 'required|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'is_default' => 'boolean'
        ]);

        $user = Auth::user();

        // Get coordinates from address if not provided
        if (empty($validated['lat']) || empty($validated['lng'])) {
            $coords = $this->getCoordinates($validated['address_line'] . ', ' . $validated['district'] . ', ' . $validated['city'] . ', Việt Nam');
            $validated['lat'] = $coords['lat'];
            $validated['lng'] = $coords['lng'];
        }

        // If this is default, unset other defaults
        if ($validated['is_default'] ?? false) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm địa chỉ mới',
            'data' => $address
        ]);
    }

    /**
     * Update an address
     */
    public function update(Request $request, $id)
    {
        $address = Auth::user()->addresses()->findOrFail($id);

        $validated = $request->validate([
            'label' => 'string|max:50',
            'recipient_name' => 'nullable|string|max:150',
            'recipient_phone' => 'nullable|string|max:20',
            'address_line' => 'string',
            'district' => 'nullable|string',
            'city' => 'string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'is_default' => 'boolean'
        ]);

        if ($validated['is_default'] ?? false) {
            Auth::user()->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
        }

        // Re-calculate coordinates if address changed and no new coordinates provided
        if ((isset($validated['address_line']) || isset($validated['district']) || isset($validated['city'])) && 
            (empty($validated['lat']) || empty($validated['lng']))) {
            $fullAddress = ($validated['address_line'] ?? $address->address_line) . ', ' . 
                          ($validated['district'] ?? $address->district) . ', ' . 
                          ($validated['city'] ?? $address->city) . ', Việt Nam';
            $coords = $this->getCoordinates($fullAddress);
            $validated['lat'] = $coords['lat'];
            $validated['lng'] = $coords['lng'];
        }

        $address->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật địa chỉ',
            'data' => $address
        ]);
    }

    /**
     * Remove an address
     */
    public function destroy($id)
    {
        $address = Auth::user()->addresses()->findOrFail($id);
        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa địa chỉ'
        ]);
    }

    /**
     * Get coordinates from address string using OpenStreetMap
     */
    private function getCoordinates($address)
    {
        try {
            $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($address) . "&limit=1";
            $client = new \GuzzleHttp\Client(['headers' => ['User-Agent' => 'PharmaVN/1.0']]);
            $response = $client->get($url);
            $data = json_decode($response->getBody(), true);
            
            if (!empty($data)) {

                return [
                    'lat' => (float)$data[0]['lat'],
                    'lng' => (float)$data[0]['lon']
                ];
            } else {
                \Log::warning("Geocoding found no results for [$address]");
            }
        } catch (\Exception $e) {
            \Log::error("Geocoding error for [$address]: " . $e->getMessage());
        }

        return ['lat' => null, 'lng' => null];
    }
}
