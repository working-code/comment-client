<?php

declare(strict_types=1);

namespace WorkingCode\CommentClient\Exception;

use Throwable;

class NetworkException extends CommentsException
{
    public function __construct(string $message, Throwable $previous)
    {
        parent::__construct($message, $previous->getCode(), $previous);
    }
}
