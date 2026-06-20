<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\LiveSession;

class AdminLiveSessionController extends Controller
{
    public function index()
    {
        $sessions = LiveSession::with('seller')->latest()->paginate(15);
        $activeCount = LiveSession::where('status', 'live')->count();
        return view('admin.live.index', compact('sessions', 'activeCount'));
    }
    public function stop($id)
    {
        LiveSession::findOrFail($id)->update(['status' => 'finished']);
        return redirect()->back()->with('success', 'Sesi live telah dihentikan.');
    }
}
