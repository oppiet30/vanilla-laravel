<?php

declare(strict_types=1);

require_once __DIR__ . '/SessionTestCase.php';

use App\Csrf;
use App\Http\Request;
use App\Session;
use App\View;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\WithEnvironmentVariable;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
#[WithEnvironmentVariable('DB_PORT', '1')]
final class UserDeletionTest extends SessionTestCase
{
    public function testMalformedIdsAreRejected(): void
    {
        $router = $this->router();
        foreach (['0', 'bad', '01', '4294967296'] as $id) {
            self::assertSame(422, $router->dispatch(new Request('GET', '/database/users/' . $id . '/delete'))->status);
            self::assertSame(422, $router->dispatch(new Request('POST', '/database/users/' . $id . '/delete', ['_token' => Csrf::token()]))->status);
        }
    }

    public function testMissingWrongAndArrayTokensAreRejectedBeforeDatabaseAccess(): void
    {
        $router = $this->router();
        foreach ([[], ['_token' => 'wrong'], ['_token' => [Csrf::token()]]] as $data) {
            $response = $router->dispatch(new Request('POST', '/database/users/1/delete', $data));
            self::assertSame(403, $response->status);
            self::assertStringContainsString('href="/database/users/1/delete"', $response->body);
        }
        self::assertNull(Session::pullFlash('user_deleted'));
    }

    public function testDatabaseFailuresAreGenericAndDoNotFlashSuccess(): void
    {
        $router = $this->router();
        foreach (['GET', 'POST'] as $method) {
            $response = $router->dispatch(new Request($method, '/database/users/1/delete', ['_token' => Csrf::token()]));
            self::assertSame(503, $response->status);
            self::assertStringNotContainsString('SQLSTATE', $response->body);
        }
        self::assertNull(Session::pullFlash('user_deleted'));
    }

    public function testDeletionRequiresPostAndRetainsMethodHandling(): void
    {
        $response = $this->router()->dispatch(new Request('DELETE', '/database/users/1/delete'));
        self::assertSame(405, $response->status);
        self::assertSame('GET, POST', $response->headers['Allow']);
    }

    public function testConfirmationEscapesValuesAndOffersCancel(): void
    {
        $html = View::render('database-user-delete', [
            'user' => ['id' => 7, 'name' => '<script>bad</script>'],
            'csrfToken' => 'token" onclick="bad',
        ]);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringContainsString('&lt;script&gt;bad&lt;/script&gt;', $html);
        self::assertStringContainsString('token&quot; onclick=&quot;bad', $html);
        self::assertStringContainsString('action="/database/users/7/delete" method="post"', $html);
        self::assertStringContainsString('href="/database/users/7">Cancel', $html);
    }
}
