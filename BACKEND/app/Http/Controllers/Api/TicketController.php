<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'data' => $tickets
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'issue_title'    => 'required|string|max:150',
            'issue_category' => 'required|string|max:100',
            'description'    => 'required|string',
        ]);

        $ticket = Ticket::create([
            'user_id'        => $request->user()->id,
            'issue_title'    => $data['issue_title'],
            'issue_category' => $data['issue_category'],
            'description'    => $data['description'],
            'status'         => 'open',
        ]);

        return response()->json([
            'message' => 'Tiket dukungan berhasil dibuat.',
            'data'    => $ticket
        ], 201);
    }
}
