<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

class AdminTicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::with('user')->orderBy('created_at', 'desc')->get();
        return view('admin.tickets', compact('tickets'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
            'admin_reply' => 'nullable|string'
        ]);

        $ticket = Ticket::findOrFail($id);
        $ticket->update([
            'status' => $request->status,
            'admin_reply' => $request->admin_reply,
        ]);

        return redirect()->back()->with('success', 'Status dan balasan tiket berhasil diupdate.');
    }
}
