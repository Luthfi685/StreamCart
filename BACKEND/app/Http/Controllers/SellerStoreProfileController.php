<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SellerStoreProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        return view('seller.store-profile.index', compact('user'));
    }
    public function update(Request $request)
    {
        $request->validate([
            'store_name' => 'required|string|max:255',
            'store_description' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'bank_account' => 'nullable|string',
            'bank_account_name' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);
        
        $data = [
            'store_name' => $request->store_name,
            'store_description' => $request->store_description,
            'bank_name' => $request->bank_name,
            'bank_account' => $request->bank_account,
            'bank_account_name' => $request->bank_account_name,
        ];

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = '/storage/' . $path;
        }

        $request->user()->update($data);
        return redirect()->back()->with('success', 'Profil toko berhasil diperbarui!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password saat ini tidak sesuai.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()->with('success', 'Password berhasil diubah!');
    }
}
