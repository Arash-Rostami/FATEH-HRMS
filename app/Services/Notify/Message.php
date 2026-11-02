<?php

namespace App\Services\Notify;

final readonly class Message
{
    public function __construct(
        public string $subject,
        public array $lines,
        public string $ruleName,
        public string $modelLabel,
        public string $event,
        public string $template = 'plain',
        public ?string $url = null,
        public ?string $action = null,
    ) {}

    public function text(): string
    {
        return $this->subject."\n".implode("\n", $this->lines);
    }
}
