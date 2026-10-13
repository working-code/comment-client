<?php

declare(strict_types=1);

namespace Unit\Model;

use PHPUnit\Framework\TestCase;
use WorkingCode\CommentClient\Model\CreateComment;

/**
 * @internal
 */
class CreateCommentTest extends TestCase
{
    public function testGivenNameAndTextWhenToPayloadArrayThenContainsBothFields(): void
    {
        $payload = (new CreateComment(name: 'Vlad', text: 'proxmox best'))->toPayloadArray();

        self::assertSame(['name' => 'Vlad', 'text' => 'proxmox best'], $payload);
    }

    public function testGivenCreatedCommentWhenToPayloadArrayThenDoesNotContainId(): void
    {
        $payload = (new CreateComment(name: 'Vlad', text: 'proxmox best'))->toPayloadArray();

        self::assertArrayNotHasKey('id', $payload);
    }
}
