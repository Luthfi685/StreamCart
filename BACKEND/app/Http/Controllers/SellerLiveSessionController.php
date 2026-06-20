<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\LiveSession;

class SellerLiveSessionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $sessions = LiveSession::where('seller_id', $user->id)->latest()->paginate(10);
        $activeLives = LiveSession::where('seller_id', $user->id)->where('status', 'live')->count();
        return view('seller.live.index', compact('user', 'sessions', 'activeLives'));
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'bank_name' => 'required|string',
            'bank_account' => 'required|string',
            'bank_account_name' => 'required|string',
            'release_type' => 'required|in:now,schedule',
            'schedule_date' => 'required_if:release_type,schedule|nullable|date',
            'schedule_time' => 'required_if:release_type,schedule|nullable|date_format:H:i',
        ]);
        
        $sessionData = [
            'seller_id' => $request->user()->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'bank_name' => $data['bank_name'],
            'bank_account' => $data['bank_account'],
            'bank_account_name' => $data['bank_account_name'],
        ];

        if ($data['release_type'] === 'schedule') {
            $sessionData['status'] = 'scheduled';
            $sessionData['scheduled_at'] = $data['schedule_date'] . ' ' . $data['schedule_time'] . ':00';
            $session = LiveSession::create($sessionData);
            return redirect()->route('seller.live.index')->with('success', 'Sesi live berhasil dijadwalkan!');
        } else {
            // CEK DOUBLE SESSION: Pastikan tidak ada sesi live yang sedang aktif
            $activeLive = LiveSession::where('seller_id', $request->user()->id)
                                    ->where('status', 'live')
                                    ->first();
            
            if ($activeLive) {
                // Jika sudah ada yang live, jangan buat baru. Arahkan ke studio sesi yang sudah ada.
                return redirect()->route('seller.live.studio', $activeLive->id)->with('error', 'Anda sudah memiliki sesi live yang aktif!');
            }

            $sessionData['status'] = 'live';
            $session = LiveSession::create($sessionData);
            return redirect()->route('seller.live.studio', $session->id)->with('success', 'Sesi live berhasil dimulai!');
        }
    }

    public function studio(Request $request, $id)
    {
        $user = $request->user();
        $session = LiveSession::where('seller_id', $user->id)->findOrFail($id);
        $products = \App\Models\Product::where('seller_id', $user->id)->get();
        return view('seller.live.studio', compact('user', 'session', 'products'));
    }

    public function pinProduct(Request $request, $id)
    {
        $session = LiveSession::where('seller_id', $request->user()->id)->findOrFail($id);
        $request->validate(['product_id' => 'required|exists:products,id']);
        
        $pinned = $session->pinned_products ?? [];
        
        if (in_array($request->product_id, $pinned)) {
            // Unpin
            $pinned = array_values(array_diff($pinned, [$request->product_id]));
        } else {
            // Pin
            $pinned[] = $request->product_id;
        }
        
        $session->update(['pinned_products' => $pinned]);
        
        $products = \App\Models\Product::whereIn('id', $pinned)->get();
        try {
            event(new \App\Events\ProductsPinned($session->id, $products));
        } catch (\Exception $e) {
            \Log::error('Broadcast error: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Pin status updated', 'products' => $products, 'pinned_ids' => $pinned]);
    }
    public function end(Request $request, $id)
    {
        $session = LiveSession::where('seller_id', $request->user()->id)->findOrFail($id);
        $session->update(['status' => 'finished', 'pinned_product_id' => null]);
        try {
            event(new \App\Events\LiveStreamEnded($session->id));
        } catch (\Exception $e) {
            \Log::error('Broadcast error: ' . $e->getMessage());
        }
        return redirect()->route('seller.live.index')->with('success', 'Sesi live telah diakhiri.');
    }

    public function startScheduled(Request $request, $id)
    {
        $session = LiveSession::where('seller_id', $request->user()->id)->findOrFail($id);
        if ($session->status !== 'scheduled') {
            return redirect()->back()->with('error', 'Hanya sesi terjadwal yang bisa dimulai.');
        }
        $session->update(['status' => 'live']);
        return redirect()->route('seller.live.studio', $session->id)->with('success', 'Sesi live berhasil dimulai!');
    }
}
