<?php

declare(strict_types=1);

namespace Unit;

use Exception;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use JsonException;
use PHPUnit\Event\NoPreviousThrowableException;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WorkingCode\CommentClient\CommentsClient;
use WorkingCode\CommentClient\Exception\EmptyUpdateException;
use WorkingCode\CommentClient\Exception\HttpException;
use WorkingCode\CommentClient\Exception\InvalidResponseException;
use WorkingCode\CommentClient\Exception\NetworkException;
use WorkingCode\CommentClient\Model\Comment;
use WorkingCode\CommentClient\Model\CreateComment;
use WorkingCode\CommentClient\Model\UpdateComment;

/**
 * @internal
 */
class CommentsClientTest extends TestCase
{
    private const string HTML_RESPONSE = '<html><body>502 Bad Gateway</body></html>';
    private CommentsClient $commentsClient;
    private ClientInterface $httpClient;

    /**
     * @throws NoPreviousThrowableException
     */
    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->commentsClient = new CommentsClient($this->httpClient);
    }

    /**
     * @throws NetworkException
     * @throws HttpException
     * @throws InvalidResponseException
     * @throws JsonException
     */
    public function testGivenCommentsListResponseWhenListCommentsThenReturnsMappedComments(): void
    {
        $body = '[{"id": 1, "name": "Alena", "text": "First!"}, {"id": 2, "name": "Vovan", "text": "Nice project"}]';
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $body))
        ;

        $comments = $this->commentsClient->getComments();

        self::assertCount(2, $comments);
        self::assertInstanceOf(Comment::class, $comments[0]);
        self::assertSame(1, $comments[0]->id);
        self::assertSame('Alena', $comments[0]->name);
        self::assertSame('First!', $comments[0]->text);
        self::assertSame(2, $comments[1]->id);
        self::assertSame('Vovan', $comments[1]->name);
    }

    public function testGivenEmptyListResponseWhenListCommentsThenReturnsEmptyArray(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], '[]'))
        ;

        self::assertSame([], $this->commentsClient->getComments());
    }

    /**
     * @throws JsonException
     * @throws NetworkException
     * @throws HttpException
     */
    public function testGivenNonArrayResponseWhenListCommentsThenThrowsInvalidResponseException(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], '{"unexpected":"shape"}'))
        ;

        $this->expectException(InvalidResponseException::class);
        $this->commentsClient->getComments();
    }

    /**
     * @throws JsonException
     * @throws NetworkException
     * @throws HttpException
     */
    public function testGivenBrokenJsonBodyWhenListCommentsThenThrowsInvalidResponseException(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(200, [], self::HTML_RESPONSE))
        ;

        $this->expectException(InvalidResponseException::class);
        $this->commentsClient->getComments();
    }

    /**
     * @throws HttpException
     * @throws InvalidResponseException
     * @throws JsonException
     * @throws NetworkException
     */
    public function testGivenListCommentsWhenSendsRequestThenUsesGetMethodUrlAndAcceptHeader(): void
    {
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with(self::callback(static function (RequestInterface $request): bool {
                return $request->getMethod() === 'GET'
                    && (string) $request->getUri() === 'https://example.com/comments'
                    && $request->getHeaderLine('Accept') === 'application/json'
                    && (string) $request->getBody() === '';
            }))
            ->willReturn(new Response(200, [], '[]'))
        ;

        $this->commentsClient->getComments();
    }

    /**
     * @throws NetworkException
     * @throws HttpException
     * @throws JsonException
     * @throws InvalidResponseException
     */
    public function testGivenCreateCommentWhenCreateCommentThenSendsPostAndMapsServerId(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(201, [], '{"id": 7, "name": "Ivan", "text": "hi"}'))
        ;

        $comment = $this->commentsClient
            ->createComment(new CreateComment(name: 'Ivan', text: 'hi'))
        ;

        self::assertSame(7, $comment->id);
        self::assertSame('Ivan', $comment->name);
        self::assertSame('hi', $comment->text);
    }

    /**
     * @throws NetworkException
     * @throws HttpException
     * @throws JsonException
     * @throws InvalidResponseException
     */
    public function testGivenCreateCommentWhenSendsRequestThenPostsJsonWithoutId(): void
    {
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with(self::callback(static function (RequestInterface $request): bool {
                return $request->getMethod() === 'POST'
                    && (string) $request->getUri() === 'https://example.com/comment'
                    && $request->getHeaderLine('Content-Type') === 'application/json'
                    && json_decode((string) $request->getBody(), true) === ['name' => 'Ivan', 'text' => 'hi'];
            }))
            ->willReturn(new Response(201, [], '{"id": 7, "name": "Ivan", "text": "hi"}'))
        ;

        $this->commentsClient
            ->createComment(new CreateComment(name: 'Ivan', text: 'hi'))
        ;
    }

    /**
     * @throws EmptyUpdateException
     * @throws ExpectationFailedException
     * @throws HttpException
     * @throws InvalidResponseException
     * @throws JsonException
     * @throws NetworkException
     */
    public function testGivenFullUpdateCommentWhenUpdateCommentThenSendsPutWithBothFields(): void
    {
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with(self::callback(static function (RequestInterface $request): bool {
                return $request->getMethod() === 'PUT'
                    && (string) $request->getUri() === 'https://example.com/comment/7'
                    && json_decode((string) $request->getBody(), true) === ['name' => 'Ivan', 'text' => 'updated text'];
            }))
            ->willReturn(new Response(200, [], '{"id": 7, "name": "Ivan", "text": "updated text"}'))
        ;

        $comment = $this->commentsClient
            ->updateComment(new UpdateComment(id: 7, name: 'Ivan', text: 'updated text'))
        ;

        self::assertSame(7, $comment->id);
        self::assertSame('updated text', $comment->text);
    }

    /**
     * @throws EmptyUpdateException
     * @throws HttpException
     * @throws InvalidResponseException
     * @throws JsonException
     * @throws NetworkException
     */
    public function testGivenPartialUpdateCommentWhenUpdateCommentThenSendsOnlySetFields(): void
    {
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with(self::callback(static function (RequestInterface $request): bool {
                return (string) $request->getUri() === 'https://example.com/comment/42'
                    && json_decode((string) $request->getBody(), true) === ['text' => 'only text'];
            }))
            ->willReturn(new Response(200, [], '{"id": 42, "name": "Diman", "text": "only text"}'))
        ;

        $this->commentsClient
            ->updateComment(new UpdateComment(id: 42, text: 'only text'))
        ;
    }

    /**
     * @throws EmptyUpdateException
     * @throws HttpException
     * @throws InvalidResponseException
     * @throws JsonException
     * @throws NetworkException
     */
    public function testGivenPartialUpdateWithNameOnlyWhenUpdateCommentThenSendsOnlyName(): void
    {
        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with(self::callback(static function (RequestInterface $req): bool {
                return (string) $req->getUri() === 'https://example.com/comment/42'
                    && json_decode((string) $req->getBody(), true) === ['name' => 'Diman'];
            }))
            ->willReturn(new Response(200, [], '{"id": 42, "name": "Diman", "text": "old"}'))
        ;

        $this->commentsClient
            ->updateComment(new UpdateComment(id: 42, name: 'Diman'))
        ;
    }

    /**
     * @throws AssertionFailedError
     * @throws EmptyUpdateException
     * @throws ExpectationFailedException
     * @throws InvalidResponseException
     * @throws JsonException
     * @throws NetworkException
     */
    public function testGiven404ResponseWhenUpdateCommentThenThrowsHttpExceptionWithStatusAndBody(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(404, [], '{"error": "not found", "message": "comment 99 does not exist"}'))
        ;

        try {
            $this->commentsClient
                ->updateComment(new UpdateComment(id: 99, text: 'x'))
            ;
            self::fail('HttpException was not thrown');
        } catch (HttpException $e) {
            self::assertSame(404, $e->getStatusCode());
            self::assertStringContainsString('comment 99 does not exist', $e->getResponseBody());
        }
    }

    /**
     * @throws InvalidResponseException
     * @throws JsonException
     * @throws NetworkException
     * @throws AssertionFailedError
     * @throws ExpectationFailedException
     */
    public function testGiven502ResponseWhenListCommentsThenThrowsHttpExceptionWithStatus(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willReturn(new Response(502, [], self::HTML_RESPONSE))
        ;

        try {
            $this->commentsClient->getComments();
            self::fail('HttpException was not thrown');
        } catch (HttpException $e) {
            self::assertSame(502, $e->getStatusCode());
        }
    }

    /**
     * @throws InvalidResponseException
     */
    public function testGivenConnectionRefusedWhenListCommentsThenThrowsNetworkException(): void
    {
        $this->httpClient
            ->method('sendRequest')
            ->willThrowException(
                new ConnectException('Connection refused', new Request('GET', 'http://example.com/comments'))
            )
        ;

        try {
            $this->commentsClient->getComments();
            self::fail('NetworkException was not thrown');
        } catch (NetworkException $e) {
            self::assertInstanceOf(NetworkExceptionInterface::class, $e->getPrevious());
            self::assertStringContainsString('Connection refused', $e->getMessage());
        }
    }

    /**
     * @throws EmptyUpdateException
     * @throws NetworkException
     * @throws InvalidArgumentException
     * @throws InvalidResponseException
     * @throws JsonException
     */
    public function testGivenExceptionCarryingResponseWhenUpdateCommentThenThrowsHttpException(): void
    {
        $badResponse = new Response(404, [], '{"error": "not found", "message": "comment 99 does not exist"}');

        $this->httpClient
            ->method('sendRequest')
            ->willThrowException(new class($badResponse) extends Exception implements NetworkExceptionInterface {
                public function __construct(private readonly ResponseInterface $response)
                {
                    parent::__construct('404 from non-conforming client');
                }

                public function getRequest(): RequestInterface
                {
                    return new Request('PUT', 'https://example.com/comment/99');
                }

                public function getResponse(): ResponseInterface
                {
                    return $this->response;
                }
            })
        ;

        try {
            $this->commentsClient->updateComment(new UpdateComment(id: 99, text: 'x'));
            self::fail('HttpException was not thrown');
        } catch (HttpException $e) {
            self::assertSame(404, $e->getStatusCode());
            self::assertStringContainsString('comment 99 does not exist', $e->getResponseBody());
        }
    }

    /**
     * @throws HttpException
     * @throws InvalidResponseException
     * @throws JsonException
     * @throws NetworkException
     */
    public function testGivenCustomBaseUrlWithTrailingSlashWhenListCommentsThenBuildsUrlWithoutDoubleSlash(): void
    {
        $client = new CommentsClient($this->httpClient, 'https://staging.example.com/');

        $this->httpClient
            ->expects(self::once())
            ->method('sendRequest')
            ->with(self::callback(static fn (RequestInterface $req): bool => (string) $req->getUri() === 'https://staging.example.com/comments'))
            ->willReturn(new Response(200, [], '[]'))
        ;

        $client->getComments();
    }
}
