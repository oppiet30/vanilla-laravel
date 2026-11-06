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
final class UserNameUniquenessIntegrationTest extends SessionTestCase
{
    private function database(): PDO
    {
        if (getenv('VANILLA_TEST_DB') !== '1') {
            self::markTestSkipped('Set VANILLA_TEST_DB=1 for transactional MariaDB tests.');
        }
        $db = Database::connection();
        self::assertSame('InnoDB', $db->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'demo_users'")->fetchColumn());
        return $db;
    }

    public function testCreateAndEditRejectDuplicateNamesButAllowUnchangedName(): void
    {
        $db = $this->database();
        $db->beginTransaction();
        try {
            $name = 'Unique-test-' . bin2hex(random_bytes(8));
            $id = DemoUser::create($name);
            $otherId = DemoUser::create($name . '-other');
            self::assertTrue(DemoUser::nameExists($name));
            self::assertFalse(DemoUser::nameExists($name, $id));
            self::assertTrue(DemoUser::nameExists($name, $otherId));
            $router = $this->router();
            $token = Csrf::token();
            foreach ([$name, strtoupper($name), '  ' . $name . '  '] as $duplicate) {
                $response = $router->dispatch(new Request('POST', '/database/users', ['_token' => $token, 'name' => $duplicate]));
                self::assertSame(422, $response->status);
                self::assertStringContainsString('A user with this name already exists.', $response->body);
                self::assertStringContainsString('aria-describedby="name-error"', $response->body);
            }
            $response = $router->dispatch(new Request('POST', '/database/users/' . $otherId, ['_token' => $token, 'name' => $name]));
            self::assertSame(422, $response->status);
            self::assertSame($name . '-other', DemoUser::find($otherId)['name']);
            self::assertSame(303, $router->dispatch(new Request('POST', '/database/users/' . $id, ['_token' => $token, 'name' => $name]))->status);
            $count = $db->prepare('SELECT COUNT(*) FROM demo_users WHERE name = :name');
            $count->execute(['name' => $name]);
            self::assertSame(1, (int) $count->fetchColumn());
        } finally {
            $db->rollBack();
        }
    }

    public function testUniqueIndexRejectsDuplicatesOutsideTheController(): void
    {
        $db = $this->database();
        $index = $db->query("SHOW INDEX FROM demo_users WHERE Key_name = 'demo_users_name_unique'")->fetch();
        if ($index === false) {
            self::markTestSkipped('Apply database/migrations/001_unique_demo_user_names.sql after resolving existing duplicates.');
        }
        self::assertSame(0, (int) $index['Non_unique']);
        $db->beginTransaction();
        try {
            $name = 'Unique-index-' . bin2hex(random_bytes(8));
            DemoUser::create($name);
            try {
                DemoUser::create($name);
                self::fail('The database accepted a duplicate name.');
            } catch (PDOException $error) {
                self::assertSame(1062, (int) $error->errorInfo[1]);
            }
        } finally {
            $db->rollBack();
        }
    }
}
