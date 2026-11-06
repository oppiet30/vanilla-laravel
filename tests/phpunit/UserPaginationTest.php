<?php

declare(strict_types=1);

require_once __DIR__ . '/SessionTestCase.php';

use App\Http\Request;
use App\View;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\WithEnvironmentVariable;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
#[WithEnvironmentVariable('DB_PORT', '1')]
final class UserPaginationTest extends SessionTestCase
{
    public function testInvalidPagesAreRejectedBeforeDatabaseAccess(): void
    {
        $router = $this->router();
        foreach (['0', '-1', '01', '', '1.5', 'bad', '1000000000', '1%20OR%201=1'] as $value) {
            self::assertSame(422, $router->dispatch(new Request('GET', '/database?page=' . $value))->status);
        }
        self::assertSame(422, $router->dispatch(new Request('GET', '/database?page[]=1'))->status);
    }

    public function testDefaultAndValidPagesRetainGenericDatabaseFailure(): void
    {
        foreach (['/database', '/database?page=2'] as $path) {
            $response = $this->router()->dispatch(new Request('GET', $path));
            self::assertSame(503, $response->status);
            self::assertStringNotContainsString('SQLSTATE', $response->body);
        }
    }

    public function testQueryPresenceDistinguishesMissingFromArrayValues(): void
    {
        self::assertFalse((new Request('GET', '/database'))->hasQuery('page'));
        $request = new Request('GET', '/database?page[]=1');
        self::assertTrue($request->hasQuery('page'));
        self::assertNull($request->query('page'));
        $request = new Request('POST', '/database?page=2', ['page' => '3']);
        self::assertSame('2', $request->query('page'));
        self::assertSame('3', $request->input('page'));
    }

    public function testNavigationReflectsFirstMiddleLastAndEmptyPages(): void
    {
        foreach ([[1, 3, false, true], [2, 3, true, true], [3, 3, true, false], [1, 1, false, false]] as [$page, $last, $prev, $next]) {
            $html = View::render('database', [
                'users' => [], 'unavailable' => false,
                'pagination' => ['page' => $page, 'lastPage' => $last, 'total' => 0],
            ]);
            self::assertSame($prev, str_contains($html, 'rel="prev"'));
            self::assertSame($next, str_contains($html, 'rel="next"'));
            self::assertStringContainsString('Page ' . $page . ' of ' . $last, $html);
            if ($prev) {
                self::assertStringContainsString('/database?page=' . ($page - 1), $html);
            }
            if ($next) {
                self::assertStringContainsString('/database?page=' . ($page + 1), $html);
            }
        }
    }
}
