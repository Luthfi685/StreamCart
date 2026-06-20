<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    /**
     * Get all addresses for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = UserAddress::where('user_id', $request->user()->id)
            ->orderBy('is_default', 'desc')
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Berhasil mengambil data alamat',
            'data'    => $addresses
        ], 200);
    }

    /**
     * Store a new address or update existing default status.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'receiver_name'  => 'required|string|max:100',
            'phone'          => 'required|max:20',
            'region'         => 'required|string',
            'location_name'  => 'required|string',
            'address_detail' => 'nullable|string',
            'is_default'     => 'boolean',
        ]);

        // If the user wants to set this as default, un-default others
        if (!empty($data['is_default'])) {
            UserAddress::where('user_id', $request->user()->id)
                ->update(['is_default' => false]);
        } else {
            // If it's the first address, make it default automatically
            $count = UserAddress::where('user_id', $request->user()->id)->count();
            if ($count === 0) {
                $data['is_default'] = true;
            } else {
                $data['is_default'] = false;
            }
        }

        $address = UserAddress::create([
            'user_id'        => $request->user()->id,
            'receiver_name'  => $data['receiver_name'],
            'phone'          => $data['phone'],
            'region'         => $data['region'],
            'location_name'  => $data['location_name'],
            'address_detail' => $data['address_detail'] ?? null,
            'is_default'     => $data['is_default'],
        ]);

        return response()->json([
            'message' => 'Alamat berhasil disimpan',
            'data'    => $address
        ], 201); // 201 Created
    }

    /**
     * Delete an address
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $address = UserAddress::where('user_id', $request->user()->id)->findOrFail($id);
        $address->delete();

        return response()->json([
            'message' => 'Alamat berhasil dihapus'
        ], 200);
    }
}
