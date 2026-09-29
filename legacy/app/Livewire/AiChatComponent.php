<?php

namespace App\Livewire;

use App\Services\Ai\AiChatService;
use Livewire\Component;

class AiChatComponent extends Component
{
     public string $input = '';
    public array $messages = [];

    protected $listeners = [
        'maquinaria_insertada' => 'onMaquinariaInsertada'
    ];

    public function mount()
    {
        $this->messages[] = [
            'role' => 'assistant',
            'content' => 'Hola, ¿en qué puedo ayudarte?'
        ];
    }

    public function send()
    {
        $this->messages[] = [
            'role' => 'user',
            'content' => $this->input
        ];

        $response = app(AiChatService::class)
            ->handle($this->messages, $this->input);

        $this->messages[] = [
            'role' => 'assistant',
            'content' => $response['message']
        ];

        if (!empty($response['action'])) {
            $this->dispatchAction($response);
        }

        $this->input = '';
    }

    protected function dispatchAction(array $payload)
    {
        match ($payload['action']) {
            'INSERTAR_MAQUINARIAS' =>
                event(new \App\Events\MaquinariaInsertada($payload['data'])),

            default => null,
        };
    }

    public function onMaquinariaInsertada($data)
    {
        $this->messages[] = [
            'role' => 'system',
            'content' => 'Maquinarias registradas correctamente.'
        ];
    }

    public function render()
    {
        return view('livewire.ai-chat-component');
    }
}
