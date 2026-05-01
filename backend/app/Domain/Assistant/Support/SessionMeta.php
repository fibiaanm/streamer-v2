<?php

namespace App\Domain\Assistant\Support;

class SessionMeta
{
    private function __construct(
        private int $messageCount,
        private int $responses,
        private int $inputTokens,
        private int $outputTokens,
        private int $totalTokens,
    ) {}

    public static function fromArray(?array $data): self
    {
        $cost = $data['cost'] ?? [];

        return new self(
            messageCount: $data['message_count'] ?? 0,
            responses:    $data['responses']     ?? 0,
            inputTokens:  $cost['input']         ?? 0,
            outputTokens: $cost['output']        ?? 0,
            totalTokens:  $cost['total']         ?? 0,
        );
    }

    public function incrementMessageCount(): void
    {
        $this->messageCount++;
    }

    public function addResponse(): void
    {
        $this->responses++;
    }

    public function addCost(int $input, int $output): void
    {
        $this->inputTokens  += $input;
        $this->outputTokens += $output;
        $this->totalTokens  += $input + $output;
    }

    public function toArray(): array
    {
        return [
            'message_count' => $this->messageCount,
            'responses'     => $this->responses,
            'cost'          => [
                'input'  => $this->inputTokens,
                'output' => $this->outputTokens,
                'total'  => $this->totalTokens,
            ],
        ];
    }
}
