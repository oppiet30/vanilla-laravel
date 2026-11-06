<?php

declare(strict_types=1);

require_once __DIR__ . '/SessionTestCase.php';

use App\Csrf;
use App\Database;
use App\Http\Request;
use App\Models\DemoUser;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
#[Group('integration')]
final class UserDeletionIntegrationTest extends SessionTestCase
{
    public function testOnlyConfirmedUserIsDeletedAndNoticeAppearsOnce(): void
    {
        if (getenv('VANILLA_TEST_DB') !== '1') {
            self::markTestSkipped('Set VANILLA_TEST_DB=1 to run the transactional MariaDB test.');
        }

        $database = Database::connection();
        $engine = $database->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'demo_users'")->fetchColumn();
        self::assertSame('InnoDB', $engine, 'The integration test requires rollback-capable InnoDB.');
        $database->beginTransaction();
        try {
            $marker = 'PHPUnit-delete-' . bin2hex(random_bytes(8));
            $id = DemoUser::create($marker);
            $otherId = DemoUser::create($marker . '-keep');
            $router = $this->router();
            $path = '/database/users/' . $id . '/delete';

            $confirmation = $router->dispatch(new Request('GET', $path));
            self::assertSame(200, $confirmation->status);
            self::assertStringContainsString($marker, $confirmation->body);
            self::assertNotNull(DemoUser::find($id), 'GET confirmation must not delete.');
            self::assertSame(403, $router->dispatch(new Request('POST', $path, ['_token' => 'wrong']))->status);
            self::assertNotNull(DemoUser::find($id));

            $response = $router->dispatch(new Request('POST', $path, ['_token' => Csrf::token(), 'id' => $otherId]));
            self::assertSame(303, $response->status);
            self::assertSame('/database', $response->headers['Location']);
            self::assertNull(DemoUser::find($id));
            self::assertNotNull(DemoUser::find($otherId), 'Body ID must not override path ID.');
            self::assertStringContainsString('User deleted successfully.', $router->dispatch(new Request('GET', '/database'))->body);
            self::assertStringNotContainsString('User deleted successfully.', $router->dispatch(new Request('GET', '/database'))->body);
            self::assertSame(404, $router->dispatch(new Request('GET', $path))->status);
            self::assertSame(404, $router->dispatch(new Request('POST', $path, ['_token' => Csrf::token()]))->status);
            self::assertFalse(DemoUser::delete($id));
        } finally {
            $database->rollBack();
        }
    }
}
