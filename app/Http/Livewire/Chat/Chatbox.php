<?php

namespace App\Http\Livewire\Chat;

use App\Events\MessageSent;
use App\Events\MessageRead;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Livewire\Component;
// MessageSent


class Chatbox extends Component
{
    public $selectedConversation;
    public $receiverInstance;
    public $messages_count;
    public $messages;
    public $height;
    public $paginate_var = 10;

    // protected $listeners = [
    //     'loadConversation',
    //     'pushMessage',
    //     'loadmore',
    //     'updateHeight'
    // ];


    public function getListeners()
    {
        $auth_id = auth()->user()->id;
        return [
            "echo-private:chat.{$auth_id},MessageSent" => 'broadcastedMessageReceived',
            "echo-private:chat.{$auth_id},MessageRead" => 'broadcastedMessageRead',
            'loadConversation',
            'pushMessage',
            'loadmore',
            'updateHeight',
            'broadcastMessageRead',
            'resetComponent'
        ];
    }


    public function resetComponent()
    {
        $this->selectedConversation = null;
        $this->receiverInstance = null;
    }

    public function broadcastedMessageRead($event)
    {
        // dd($event);
        if ($this->selectedConversation) {
            if ((int)$this->selectedConversation->id === (int)$event['conversation_id']) {
                $this->dispatchBrowserEvent('markMessageAsRead');
            }
        }
    }


    public function broadcastedMessageReceived($event)
    {

        $this->emitTo('chat.chat-list', 'refresh');

        $BroadcastedMessage = Message::find($event['message']);
        if ($this->selectedConversation) {
            if ((int)$this->selectedConversation->id === (int)$event['conversation_id']) {
                $BroadcastedMessage->read = 1;
                $BroadcastedMessage->save();
                $this->pushMessage($BroadcastedMessage->id);

                $this->emitSelf('broadcastMessageRead');
            }
        }
    }

    public function broadcastMessageRead()
    {
        broadcast(new MessageRead($this->selectedConversation->id, $this->receiverInstance->id));
    }


    public function render()
    {
        return view('livewire.chat.chatbox');
    }

    public function loadmore()
    {
        $this->paginate_var += 10;

        $this->messages_count = Message::where('conversation_id', $this->selectedConversation->id)->count();
        $this->messages = Message::where('conversation_id', $this->selectedConversation->id)->skip($this->messages_count - $this->paginate_var)->take($this->paginate_var)->get();

        $height = $this->height;
        $this->dispatchBrowserEvent('updateHeight', ($height));
    }

    public function loadConversation(Conversation $conversation, User $receiver)
    {
        // dd($conversation, $receiver);

        $this->selectedConversation = $conversation;
        $this->receiverInstance = $receiver;

        $this->messages_count = Message::where('conversation_id', $this->selectedConversation->id)->count();
        $this->messages = Message::where('conversation_id', $this->selectedConversation->id)->skip($this->messages_count - $this->paginate_var)->take($this->paginate_var)->get();

        $this->dispatchBrowserEvent('chatSelected');

        Message::where('conversation_id', $this->selectedConversation->id)->where('receiver_id', auth()->id())->update([
            'read' => 1
        ]);

        $this->emitSelf('broadcastMessageRead');
    }

    public function pushMessage($messageId)
    {
        $newMessage = Message::find($messageId);
        // dd($newMessage);
        $this->messages->push($newMessage);
        $this->dispatchBrowserEvent('rowChatToBottom');
    }

    public function updateHeight($height)
    {
        // dd($height);
        $this->height = $height;
    }
}
