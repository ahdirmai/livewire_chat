<?php

namespace App\Http\Livewire\Chat;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Livewire\Component;

class SendMessage extends Component
{
    public $selectedConversation;
    public $receiverInstance;
    public $body;
    public $createdMessage;

    protected $listeners = [
        'updateSendMessage',
        'dispatchMessageSent',
        'resetComponent'
    ];


    public function resetComponent()
    {
        $this->selectedConversation = null;
        $this->receiverInstance = null;
    }

    public function render()
    {
        return view('livewire.chat.send-message');
    }

    public function updateSendMessage(Conversation $conversation, User $receiver)
    {
        $this->selectedConversation = $conversation;
        $this->receiverInstance = $receiver;
    }

    public function sendMessage()
    {
        if ($this->body == null) {
            return null;
        }

        $createMessage = Message::create([
            'conversation_id' => $this->selectedConversation->id,
            'sender_id' => auth()->user()->id,
            'receiver_id' => $this->receiverInstance->id,
            'body' => $this->body
        ]);

        $this->createdMessage = $createMessage;

        $this->selectedConversation->last_time_message = $createMessage->created_at;
        $this->selectedConversation->save();
        $this->reset('body');

        $this->emitTo('chat.chatbox', 'pushMessage', $createMessage->id);
        $this->emitTo('chat.chat-list', 'refresh');


        $this->emitSelf('dispatchMessageSent');
        // dd($this->body);
    }

    public function dispatchMessageSent()
    {
        broadcast(new MessageSent(auth()->user(), $this->createdMessage, $this->selectedConversation, $this->receiverInstance));
    }
}
