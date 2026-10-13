<?php

declare(strict_types=1);

namespace Unit\Model;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WorkingCode\CommentClient\Exception\InvalidResponseException;
use WorkingCode\CommentClient\Model\Comment;

/**
 * @internal
 */
class CommentTest extends TestCase
{
    public function testGivenFullCommentArrayWhenMakeFromArrayThenReturnsPopulatedComment(): void
    {
        $comment = Comment::makeFromArray(['id' => 5, 'name' => 'Vlad', 'text' => 'hi user']);

        self::assertSame(5, $comment->id);
        self::assertSame('Vlad', $comment->name);
        self::assertSame('hi user', $comment->text);
    }

    public function testGivenStringIdWhenMakeFromArrayThenIdIsCastToInt(): void
    {
        $comment = Comment::makeFromArray(['id' => '42', 'name' => 'Vlad', 'text' => 'hi user']);

        self::assertSame(42, $comment->id);
    }

    #[DataProvider('invalidCommentData')]
    public function testGivenInvalidCommentDataWhenMakeFromArrayThenThrowsInvalidResponseException(array $data): void
    {
        $this->expectException(InvalidResponseException::class);

        Comment::makeFromArray($data);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidCommentData(): iterable
    {
        yield 'missing id' => [['name' => 'Vlad', 'text' => 'hi user']];
        yield 'null id' => [['id' => null, 'name' => 'Vlad', 'text' => 'hi user']];
        yield 'non-numeric string id' => [['id' => 'abc', 'name' => 'Vlad', 'text' => 'hi user']];
        yield 'float id' => [['id' => 1.5, 'name' => 'Vlad', 'text' => 'hi user']];
        yield 'bool id' => [['id' => true, 'name' => 'Vlad', 'text' => 'hi user']];
        yield 'array id' => [['id' => [1], 'name' => 'Vlad', 'text' => 'hi user']];
        yield 'missing name' => [['id' => 1, 'text' => 'hi user']];
        yield 'int name' => [['id' => 1, 'name' => 42, 'text' => 'hi user']];
        yield 'missing text' => [['id' => 1, 'name' => 'Vlad']];
        yield 'array text' => [['id' => 1, 'name' => 'Vlad', 'text' => ['hi user']]];
    }
}
