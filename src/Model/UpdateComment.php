<?php

declare(strict_types=1);

namespace WorkingCode\CommentClient\Model;

use WorkingCode\CommentClient\Exception\EmptyUpdateException;

readonly class UpdateComment
{
    /**
     * @throws EmptyUpdateException
     */
    public function __construct(
        public int $id,
        public ?string $name = null,
        public ?string $text = null,
    ) {
        if ($this->name === null && $this->text === null) {
            throw new EmptyUpdateException(
                'UpdateComment requires at least one field to update',
            );
        }
    }

    /**
     * @return array<string, string>
     */
    public function toPayloadArray(): array
    {
        $payload = [];

        if ($this->name !== null) {
            $payload[CommentFields::NAME] = $this->name;
        }
        if ($this->text !== null) {
            $payload[CommentFields::TEXT] = $this->text;
        }

        return $payload;
    }
}
