<?php

declare(strict_types=1);

namespace WorkingCode\CommentClient\Exception;

class HttpException extends CommentsException
{
    public function __construct(
        private readonly int $statusCode,
        private readonly string $responseBody,
    ) {
        parent::__construct(sprintf(
            'Server responded with %d: %s',
            $statusCode,
            $responseBody !== '' ? $responseBody : '(empty body)',
        ));
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }
}
