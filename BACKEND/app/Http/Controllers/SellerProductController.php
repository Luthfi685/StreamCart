<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;

class SellerProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $products = Product::where('seller_id', $user->id)->with('reviews')->latest()->paginate(12);
        return view('seller.products.index', compact('user', 'products'));
    }
    public function create(Request $request)
    {
        $user = $request->user();
        return view('seller.products.create', compact('user'));
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',

            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:20480',
        ]);
        
        $data['seller_id'] = $request->user()->id;

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imagePaths[] = '/storage/' . $path;
            }
        }

        if (count($imagePaths) > 0) {
            $data['images'] = $imagePaths;
            $data['image_url'] = $imagePaths[0];
        } else {
            $data['images'] = [];
        }

        Product::create($data);
        return redirect()->route('seller.products.index')->with('success', 'Produk berhasil ditambahkan!');
    }
    public function edit(Request $request, $id)
    {
        $user = $request->user();
        $product = Product::where('seller_id', $user->id)->findOrFail($id);
        return view('seller.products.edit', compact('user', 'product'));
    }
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $product = Product::where('seller_id', $user->id)->findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',

            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:20480',
        ]);

        if ($request->hasFile('images')) {
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $imagePaths[] = '/storage/' . $path;
            }
            $data['images'] = $imagePaths;
            $data['image_url'] = $imagePaths[0];
        }

        $product->update($data);
        return redirect()->route('seller.products.index')->with('success', 'Produk berhasil diperbarui!');
    }
    public function destroy(Request $request, $id)
    {
        $product = Product::where('seller_id', $request->user()->id)->findOrFail($id);
        $product->delete();
        return redirect()->route('seller.products.index')->with('success', 'Produk berhasil dihapus!');
    }
}
