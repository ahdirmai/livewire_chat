<?php

namespace App\Http\Livewire\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Livewire\Component;

class CreateChat extends Component
{
    public $users;
    public $message = 'Hello How Are You';

    public function checkConversation($receiverId)
    {
        // dd($receiverId);

        $checkedConverstaion = Conversation::where('receiver_id', auth()->user()->id)->where('sender_id', $receiverId)->orWhere('receiver_id', $receiverId)->where('sender_id', auth()->user()->id)->get();

        if (count($checkedConverstaion) == 0) {
            $createdConversation = Conversation::create(
                [
                    'receiver_id' => $receiverId,
                    'sender_id' => auth()->user()->id
                ]
            );

            $createdMessage = Message::create([
                'conversation_id' => $createdConversation->id,
                'sender_id' => auth()->user()->id,
                'receiver_id' => $receiverId,
                'body' => $this->message,

            ]);

            $createdConversation->last_time_message = $createdMessage->created_at;
            $createdConversation->save();

            dd('saved');
        } elseif (count($checkedConverstaion) >= 1) {
            dd('Conversation Exists');
        }
    }

    public function render()
    {
        $this->users = User::where('id', '!=', auth()->user()->id)->get();
        return view('livewire.chat.create-chat');
    }
}
