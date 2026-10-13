<?php

declare(strict_types=1);

namespace WorkingCode\CommentClient\Model;

use WorkingCode\CommentClient\Exception\InvalidResponseException;

readonly class Comment
{
    public function __construct(
        public int $id,
        public string $name,
        public string $text,
    ) {}

    /**
     * @param array<string, mixed> $data
     *
     * @throws InvalidResponseException
     */
    public static function makeFromArray(array $data): self
    {
        $id = filter_var($data[CommentFields::ID] ?? null, FILTER_VALIDATE_INT);

        if (
            $id === false
            || is_bool($data[CommentFields::ID] ?? null)
        ) {
            throw new InvalidResponseException('Comment id must be an integer');
        }

        if (!is_string($data[CommentFields::NAME] ?? null)) {
            throw new InvalidResponseException('Comment "name" must be a string');
        }

        if (!is_string($data[CommentFields::TEXT] ?? null)) {
            throw new InvalidResponseException('Comment "text" must be a string');
        }

        return new self(
            id: (int) $id,
            name: $data[CommentFields::NAME],
            text: $data[CommentFields::TEXT],
        );
    }
}
