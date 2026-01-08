<?php
namespace App\Services\Ai\Drivers;

use OpenAI;

class OpenAiDriver
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.key'));
    }

    public function chat(array $messages, array $tools = []): array
    {
        $payload = [
            'model' => 'gpt-5-mini',
            'messages' => $messages,
            'temperature' => 0,
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $response = $this->client->chat()->create($payload);

        return $response->toArray();
    }
}
