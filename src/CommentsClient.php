<?php

declare(strict_types=1);

namespace WorkingCode\CommentClient;

use GuzzleHttp\Psr7\Request;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use WorkingCode\CommentClient\Exception\HttpException;
use WorkingCode\CommentClient\Exception\InvalidResponseException;
use WorkingCode\CommentClient\Exception\NetworkException;
use WorkingCode\CommentClient\Model\Comment;
use WorkingCode\CommentClient\Model\CreateComment;
use WorkingCode\CommentClient\Model\UpdateComment;

class CommentsClient
{
    private string $baseUrl;

    public function __construct(
        private readonly ClientInterface $httpClient,
        string $baseUrl = 'https://example.com',
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * @return list<Comment>
     *
     * @throws HttpException
     * @throws InvalidResponseException
     * @throws JsonException
     * @throws NetworkException
     */
    public function getComments(): array
    {
        $data = $this->send('GET', '/comments');

        if (!is_array($data)) {
            throw new InvalidResponseException('Expected a JSON array of comments');
        }

        $comments = [];

        foreach ($data as $item) {
            if (is_array($item)) {
                $comments[] = Comment::makeFromArray($item);
            } else {
                throw new InvalidResponseException('Expected a JSON object describing the comment');
            }
        }

        return $comments;
    }

    /**
     * @throws NetworkException
     * @throws HttpException
     * @throws InvalidResponseException
     * @throws JsonException
     */
    public function createComment(CreateComment $comment): Comment
    {
        $data = $this->send('POST', '/comment', $comment->toPayloadArray());

        return $this->buildCommentFromArray($data);
    }

    /**
     * @throws HttpException
     * @throws InvalidResponseException
     * @throws JsonException
     * @throws NetworkException
     */
    public function updateComment(UpdateComment $update): Comment
    {
        $data = $this->send('PUT', '/comment/' . $update->id, $update->toPayloadArray());

        return $this->buildCommentFromArray($data);
    }

    /**
     * @param null|array<string, mixed> $data
     *
     * @throws InvalidResponseException
     */
    private function buildCommentFromArray(?array $data): Comment
    {
        if (!is_array($data)) {
            throw new InvalidResponseException('Expected a JSON object describing the comment');
        }

        return Comment::makeFromArray($data);
    }

    /**
     * @param null|array<string, string> $payload
     *
     * @return null|array<string, mixed>
     *
     * @throws JsonException
     * @throws NetworkException
     * @throws HttpException
     * @throws InvalidResponseException
     */
    private function send(string $method, string $path, ?array $payload = null): ?array
    {
        $headers = ['Accept' => 'application/json'];
        $body = null;

        if ($payload !== null) {
            $body = (string) json_encode($payload, JSON_THROW_ON_ERROR);
            $headers['Content-Type'] = 'application/json';
        }

        $request = new Request($method, $this->baseUrl . $path, $headers, $body);

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            /** @var null|ResponseInterface $response */
            $response = method_exists($e, 'getResponse') ? $e->getResponse() : null;

            if ($response === null) {
                throw new NetworkException(
                    'Request to ' . $this->baseUrl . ' failed: ' . $e->getMessage(),
                    $e,
                );
            }
        }

        if ($response->getStatusCode() >= 400) {
            throw new HttpException(
                $response->getStatusCode(),
                (string) $response->getBody(),
            );
        }

        $raw = (string) $response->getBody();

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidResponseException(
                'Response body is not valid JSON: ' . mb_substr($raw, 0, 1_000),
            );
        }

        return $data;
    }
}
