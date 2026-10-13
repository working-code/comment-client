<?php

declare(strict_types=1);

namespace WorkingCode\CommentClient\Model;

readonly class CreateComment
{
    public function __construct(
        public string $name,
        public string $text,
    ) {}

    /**
     * @return array{name: string, text: string}
     */
    public function toPayloadArray(): array
    {
        return [
            CommentFields::NAME => $this->name,
            CommentFields::TEXT => $this->text,
        ];
    }
}
