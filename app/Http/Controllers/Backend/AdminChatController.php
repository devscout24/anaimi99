<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Chat;
use App\Events\ChatEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminChatController extends Controller
{
    public function chatViewBlade()
    {
        // Get only Salon and Home Barber (home_barbar) users
        $users = User::whereIn('role', ['salon', 'home_barbar'])->get();

        // Get unread counts for these users specifically for admin
        $unreadCounts = [];
        $adminId = auth()->id();

        foreach ($users as $u) {
            $unreadCounts[$u->id] = Chat::where('sender_id', $u->id)
                ->where('receiver_id', $adminId)
                ->where('is_read', 0)
                ->count();
        }

        return view('backend.layouts.admin.chat.index', compact('users', 'unreadCounts'));
    }

    public function chatList()
    {
        $userId = auth()->id();

        $conversations = Chat::where('receiver_id', $userId)
            ->orWhere('sender_id', $userId)
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('conversation_id');

        return response()->json($conversations);
    }

    public function fetchConversation($receiverId)
    {
        $senderId = auth()->id();

        $chats = Chat::where(function ($q) use ($senderId, $receiverId) {
            $q->where('sender_id', $senderId)->where('receiver_id', $receiverId);
        })->orWhere(function ($q) use ($senderId, $receiverId) {
            $q->where('sender_id', $receiverId)->where('receiver_id', $senderId);
        })
            ->orderBy('created_at', 'asc')
            ->get();

        $conversationId = $chats->first()?->conversation_id;

        return response()->json([
            'status' => true,
            'chat' => $chats,
            'conversation_id' => $conversationId
        ]);
    }

    public function markAsRead($conversationId)
    {
        Chat::where('conversation_id', $conversationId)
            ->where('receiver_id', auth()->id())
            ->update(['is_read' => 1]);

        return response()->json(['status' => true]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required',
            'message' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $senderId = auth()->id();
        $receiverId = $request->receiver_id;

        // Find existing conversation ID or generate a new one
        $conversationId = Chat::where(function ($q) use ($senderId, $receiverId) {
            $q->where('sender_id', $senderId)->where('receiver_id', $receiverId);
        })->orWhere(function ($q) use ($senderId, $receiverId) {
            $q->where('sender_id', $receiverId)->where('receiver_id', $senderId);
        })->value('conversation_id') ?? (string) Str::uuid();

        $chatData = [
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $request->message,
            'conversation_id' => $conversationId,
            'is_read' => 0,
        ];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('uploads/chats', 'public');
            $chatData['image'] = $path;
        }

        $chat = Chat::create($chatData);

        broadcast(new ChatEvent($chat));

        return response()->json(['status' => true, 'chat' => $chat]);
    }

    public function chatDelete($chat_id)
    {
        $chat = Chat::find($chat_id);
        if ($chat) {
            $chat->delete();
            return response()->json(['status' => true]);
        }
        return response()->json(['status' => false], 404);
    }

    public function chatImageDelete($image_id)
    {
        // Add image deletion logic if using a separate gallery model
        return response()->json(['status' => false, 'message' => 'Not implemented']);
    }
}
