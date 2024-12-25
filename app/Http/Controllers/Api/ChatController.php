<?php

namespace App\Http\Controllers\Api;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatController extends BaseController
{
    public function index(Request $request)
    {
        $chats = Chat::with(['messages' => function($query) {
                        $query->orderBy('created_at', 'desc');
                    }])
                    ->where('user_id', $request->user()->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
                   
        return $this->sendResponse($chats, 'Chats retrieved successfully.');
    }

    public function store(Request $request)
    {
        $chat = Chat::create([
            'user_id' => $request->user()->id,
            'start_time' => now()
        ]);

        return $this->sendResponse($chat, 'Chat created successfully.');
    }

    public function show($id)
    {
        $chat = Chat::with(['messages' => function($query) {
                        $query->orderBy('created_at', 'asc');
                    }])
                    ->where('id', $id)
                    ->where('user_id', request()->user()->id)
                    ->first();
        
        if (is_null($chat)) {
            return $this->sendError('Chat not found.');
        }
        
        return $this->sendResponse($chat, 'Chat retrieved successfully.');
    }

    public function sendMessage(Request $request, $chatId)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string'
        ]);

        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors(), 400);
        }

        $chat = Chat::where('id', $chatId)
                   ->where('user_id', $request->user()->id)
                   ->first();
        
        if (is_null($chat)) {
            return $this->sendError('Chat not found.');
        }

        $message = Message::create([
            'chat_id' => $chatId,
            'content' => $request->content,
            'user_id' => $request->user()->id,
            'time_spam' => now()
        ]);

        return $this->sendResponse($message, 'Message sent successfully.');
    }

    public function getMessages($chatId)
    {
        $chat = Chat::where('id', $chatId)
                   ->where('user_id', request()->user()->id)
                   ->first();
        
        if (is_null($chat)) {
            return $this->sendError('Chat not found.');
        }

        $messages = Message::with('user')
                         ->where('chat_id', $chatId)
                         ->orderBy('created_at', 'asc')
                         ->get();

        return $this->sendResponse($messages, 'Messages retrieved successfully.');
    }
}
