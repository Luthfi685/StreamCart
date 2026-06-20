<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('seller:id,name,username,store_name')
            ->where('is_active', true)
            ->orderBy('created_at', 'desc');

        if ($request->has('seller_id')) {
            $query->where('seller_id', $request->seller_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->get());
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with([
            'seller:id,name,username,store_name,bank_name,bank_account,bank_account_name',
            'reviews.user:id,name,username,avatar'
        ])->findOrFail($id);

        return response()->json($product);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize_seller($request);

        $data = $request->validate([
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'image_url'   => 'nullable|string',
            'images'      => 'nullable|array',
            'images.*'    => 'string',
            'category'    => 'nullable|string|max:100',
        ]);

        $product = Product::create(array_merge($data, [
            'seller_id' => $request->user()->id,
            'is_active' => true,
        ]));

        ActivityLog::create([
            'user_id'     => $request->user()->id,
            'action'      => 'create_product',
            'description' => "Produk '{$product->name}' ditambahkan oleh {$request->user()->username}.",
        ]);

        return response()->json($product->load('seller:id,name,username,store_name'), 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorize_seller($request);

        $product = Product::findOrFail($id);

        if ($request->user()->role !== 'admin' && $product->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'Anda tidak berhak mengubah produk ini.'], 403);
        }

        $data = $request->validate([
            'name'        => 'sometimes|string|max:200',
            'description' => 'sometimes|nullable|string',
            'price'       => 'sometimes|numeric|min:0',
            'stock'       => 'sometimes|integer|min:0',
            'image_url'   => 'sometimes|nullable|string',
            'images'      => 'sometimes|nullable|array',
            'images.*'    => 'string',
            'category'    => 'sometimes|nullable|string|max:100',
            'is_active'   => 'sometimes|boolean',
        ]);

        $product->update($data);

        return response()->json(['message' => 'Produk berhasil diperbarui.', 'product' => $product->fresh()]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->authorize_seller($request);

        $product = Product::findOrFail($id);

        if ($request->user()->role !== 'admin' && $product->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'Anda tidak berhak menghapus produk ini.'], 403);
        }

        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus.']);
    }

    private function authorize_seller(Request $request): void
    {
        if (! in_array($request->user()->role, ['seller', 'admin'])) {
            abort(403, 'Hanya Seller atau Admin yang dapat mengelola produk.');
        }
    }

    public function recommendations(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(1)->get("http://localhost:8001/recommendations/{$userId}");
            
            if ($response->successful()) {
                $productIds = $response->json('recommended_product_ids', []);
                
                if (empty($productIds)) {
                    return response()->json([]);
                }
                
                // Fetch products and maintain order from python API
                $products = Product::with('seller:id,name,username,store_name')
                    ->whereIn('id', $productIds)
                    ->where('is_active', true)
                    ->get()
                    ->sortBy(function($product) use ($productIds) {
                        return array_search($product->id, $productIds);
                    })->values();
                    
                return response()->json($products);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Python Recommendation API Error: ' . $e->getMessage());
        }

        // Fallback if Python API is down or errors out
        $fallbackProducts = Product::with('seller:id,name,username,store_name')
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit(5)
            ->get();
            
        return response()->json($fallbackProducts);
    }
}
