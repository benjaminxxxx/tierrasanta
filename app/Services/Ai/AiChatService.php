<?php 
namespace App\Services\Ai;

use App\Services\Ai\Drivers\OpenAiDriver;
use App\Ai\ToolRegistry;

class AiChatService
{
    public function handle(array $conversation, string $input): array
    {
        $messages = array_merge(
            [
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt(),
                ],
            ],
            $conversation,
            [
                [
                    'role' => 'user',
                    'content' => $input,
                ],
            ]
        );

        return app(OpenAiDriver::class)
            ->chat($messages, ToolRegistry::all());
    }

    protected function systemPrompt(): string
    {
        return <<<PROMPT
Eres un asistente del ERP.
No ejecutas acciones.
Solo puedes usar las funciones definidas.
Si falta información, debes pedirla.
Si la acción no existe, di que no está disponible.
Responde de forma clara y profesional.
PROMPT;
    }
}
