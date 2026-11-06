<?php

declare(strict_types=1);

require_once __DIR__ . '/SessionTestCase.php';

use App\Database;
use App\Http\Request;
use App\Models\DemoUser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
#[Group('integration')]
final class UserPaginationIntegrationTest extends SessionTestCase
{
    public function testPagesAreBoundedOrderedNonOverlappingAndClamped(): void
    {
        if (getenv('VANILLA_TEST_DB') !== '1') {
            self::markTestSkipped('Set VANILLA_TEST_DB=1 for the transactional MariaDB test.');
        }
        $database = Database::connection();
        self::assertSame('InnoDB', $database->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'demo_users'")->fetchColumn());
        $database->beginTransaction();
        try {
            $before = DemoUser::paginate(1)['total'];
            $marker = 'PHPUnit-pages-' . bin2hex(random_bytes(8));
            $ids = [];
            for ($i = 0; $i < 25; $i++) {
                $ids[] = DemoUser::create($marker . '-' . $i);
            }
            $first = DemoUser::paginate(1);
            $second = DemoUser::paginate(2);
            self::assertSame($before + 25, $first['total']);
            self::assertCount(20, $first['users']);
            self::assertGreaterThanOrEqual(5, count($second['users']));
            $firstIds = array_map('strval', array_column($first['users'], 'id'));
            $secondIds = array_map('strval', array_column($second['users'], 'id'));
            self::assertSame(array_reverse(array_slice($ids, -20)), $firstIds);
            self::assertSame([], array_values(array_intersect($firstIds, $secondIds)));
            self::assertSame(array_reverse(array_slice($ids, 0, 5)), array_slice($secondIds, 0, 5));
            self::assertSame($first['lastPage'], DemoUser::paginate(999999999)['page']);
            $router = $this->router();
            $response = $router->dispatch(new Request('GET', '/database?page=2'));
            self::assertSame(200, $response->status);
            self::assertStringContainsString('rel="prev"', $response->body);
            self::assertStringContainsString($marker . '-4', $response->body);
            self::assertStringNotContainsString($marker . '-24', $response->body);
        } finally {
            $database->rollBack();
        }
    }
}
