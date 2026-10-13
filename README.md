## Тестовое задание на backend-разработчика
Необходимо реализовать клиент для абстрактного (вымышленного) сервиса
комментариев "example.com". Проект должен представлять класс или набор классов,
который будет делать http запросы к серверу.

На выходе должна получиться библиотека, который можно будет подключить через
composer к любому другому проекту.

У этого сервиса есть 3 метода:
- GET http://example.com/comments - возвращает список комментариев
- POST http://example.com/comment - добавить комментарий.
- PUT http://example.com/comment/{id} - по идентификатору комментария обновляет поля, которые были в в запросе


Объект comment содержит поля:

id - тип int. Не нужно указывать при добавлении.

name - тип string.

text - тип string.


Написать phpunit тесты, на которых будет проверяться работоспособность клиента.
Сервер example.com писать не надо! Только библиотеку для работы с ним.


## Requirements
- PHP >= 8.3
- ext-mbstring


## Installation
```bash
composer require working-code/comment-client
```


## Usage
The client accepts any PSR-18 HTTP client (`Psr\Http\Client\ClientInterface`). This package does **not** install, add Guzzle:
```bash
composer require working-code/comment-client guzzlehttp/guzzle
```

```php
use GuzzleHttp\Client;
use WorkingCode\CommentClient\CommentsClient;
use WorkingCode\CommentClient\Model\Comment;
use WorkingCode\CommentClient\Model\CreateComment;
use WorkingCode\CommentClient\Model\UpdateComment;

$client = new CommentsClient(new Client());

/** @var Comment[] $comments */
$comments = $client->getComments();

$comment = $client->createComment(new CreateComment(name: 'Ivan', text: 'hi!'));
$comment->id; 

$comment = $client->updateComment(new UpdateComment(id: 7, text: 'updated'));
```

| Exception | When |
|---|---|
| `NetworkException` | no response from the HTTP client (connection error) |
| `HttpException` | server responded with 4xx/5xx — see `getStatusCode()` / `getResponseBody()` |
| `InvalidResponseException` | response is not valid JSON or has an unexpected shape |
| `EmptyUpdateException` | `UpdateComment` created without any field to update |


## Composer scripts (via Docker)
All commands run inside the `application` service from `docker-compose.yml` (PHP 8.3, Composer included):

```bash
docker compose run --rm application composer <script>
```

| Command | Description |
|---|---|
| `docker compose run --rm application composer test` | run PHPUnit test suite |
| `docker compose run --rm application composer php-cs-fixer-diff` | show code-style violations (dry run, with diff) |
| `docker compose run --rm application composer php-cs-fixer-fix` | fix code style automatically |
| `docker compose run --rm application composer phpstan-full-scan` | run PHPStan static analysis (level 8) on `src/` |
| `docker compose run --rm application composer lint` | run cs-fixer + PHPStan + tests in one command |

