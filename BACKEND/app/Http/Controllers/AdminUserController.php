<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class AdminUserController extends Controller
{
    public function index()
    {
        $users       = User::orderBy('created_at', 'desc')->paginate(15);
        $totalUsers  = User::count();
        $totalSellers= User::where('role', 'seller')->count();
        $totalBuyers = User::where('role', 'buyer')->count();

        return view('admin.users.index', compact('users', 'totalUsers', 'totalSellers', 'totalBuyers'));
    }

    /**
     * BAN user — set is_banned = true
     */
    public function ban(Request $request, int $id)
    {
        $request->validate([
            'ban_reason' => 'nullable|string|max:500',
        ]);

        $user = User::findOrFail($id);

        if ($user->role === 'admin') {
            return redirect()->back()->withErrors(['ban' => 'Akun Admin tidak dapat di-ban.']);
        }

        $user->update([
            'is_banned'  => true,
            'ban_reason' => $request->input('ban_reason', 'Melanggar ketentuan layanan StreamCart.'),
        ]);

        return redirect()->back()->with('success', "Akun {$user->name} berhasil ditangguhkan.");
    }

    /**
     * UNBAN user — set is_banned = false
     */
    public function unban(int $id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'is_banned'  => false,
            'ban_reason' => null,
        ]);

        return redirect()->back()->with('success', "Akun {$user->name} berhasil dipulihkan.");
    }
}
