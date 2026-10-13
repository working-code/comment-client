<?php

declare(strict_types=1);

namespace Unit\Model;

use PHPUnit\Framework\TestCase;
use WorkingCode\CommentClient\Exception\CommentsException;
use WorkingCode\CommentClient\Exception\EmptyUpdateException;
use WorkingCode\CommentClient\Model\UpdateComment;

/**
 * @internal
 */
class UpdateCommentTest extends TestCase
{
    /**
     * @throws EmptyUpdateException
     */
    public function testGivenOnlyTextWhenToPayloadArrayThenSendsOnlyText(): void
    {
        $payload = (new UpdateComment(id: 7, text: 'only text'))
            ->toPayloadArray()
        ;

        self::assertSame(['text' => 'only text'], $payload);
        self::assertArrayNotHasKey('name', $payload);
        self::assertArrayNotHasKey('id', $payload);
    }

    /**
     * @throws EmptyUpdateException
     */
    public function testGivenNameAndTextWhenToPayloadArrayThenSendsBothFields(): void
    {
        $payload = (new UpdateComment(id: 7, name: 'Nata', text: 'v2'))
            ->toPayloadArray()
        ;

        self::assertSame(['name' => 'Nata', 'text' => 'v2'], $payload);
    }

    public function testGivenNoFieldsWhenConstructedThenThrowsEmptyUpdateException(): void
    {
        $this->expectException(EmptyUpdateException::class);

        new UpdateComment(id: 7);
    }

    /**
     * @throws EmptyUpdateException
     */
    public function testGivenEmptyUpdateExceptionWhenCaughtAsBaseThenIsCommentsException(): void
    {
        $this->expectException(CommentsException::class);

        new UpdateComment(id: 7);
    }
}
