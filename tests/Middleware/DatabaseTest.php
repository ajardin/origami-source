<?php

declare(strict_types=1);

namespace App\Tests\Middleware;

use App\Environment\EnvironmentCollection;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
use App\Middleware\Database;
use App\Tests\TestLocationTrait;
use Ergebnis\Environment\FakeVariables;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \App\Middleware\Database
 */
final class DatabaseTest extends TestCase
{
    use TestLocationTrait;

    /** @var string */
    private $databasePath;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createLocation();
        $this->databasePath = $this->location.\DIRECTORY_SEPARATOR.Database::DATABASE_FILENAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeLocation();
    }

    public function testItCreatesTheDatabaseFile(): void
    {
        static::assertFileNotExists($this->databasePath);

        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        new Database($fakeVariables);

        static::assertFileExists($this->databasePath);
    }

    public function testItThrowsAnExceptionIfTheDatabaseIsNotCreated(): void
    {
        $this->expectException(InvalidEnvironmentException::class);

        $fakeVariables = FakeVariables::fromArray(['HOME' => '/fake/location']);
        new Database($fakeVariables);
    }

    public function testItRetrievesTheEnvironmentList(): void
    {
        file_put_contents($this->databasePath, $this->getFakeDatabaseContent());

        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);
        $environments = $database->getAllEnvironments();

        static::assertEquals(
            new EnvironmentCollection(
                [
                    new EnvironmentEntity(
                        'fake-environment-1',
                        '/fake/location/1',
                        'magento',
                        'magento.localhost www.magento.localhost',
                        false
                    ),
                    new EnvironmentEntity(
                        'fake-environment-2',
                        '/fake/location/2',
                        'symfony',
                        'symfony.localhost www.symfony.localhost',
                        true
                    ),
                ]
            ),
            $environments
        );
        static::assertCount(2, $environments);
    }

    public function testItRetrievesTheActiveEnvironment(): void
    {
        file_put_contents($this->databasePath, $this->getFakeDatabaseContent());
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        static::assertEquals(
            new EnvironmentEntity(
                'fake-environment-2',
                '/fake/location/2',
                'symfony',
                'symfony.localhost www.symfony.localhost',
                true
            ),
            $database->getActiveEnvironment()
        );

        file_put_contents($this->databasePath, '[]');
        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        static::assertNull($database->getActiveEnvironment());
    }

    public function testItRetrievesAnEnvironmentByName(): void
    {
        file_put_contents($this->databasePath, $this->getFakeDatabaseContent());

        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        static::assertEquals(
            new EnvironmentEntity(
                'fake-environment-1',
                '/fake/location/1',
                'magento',
                'magento.localhost www.magento.localhost',
                false
            ),
            $database->getEnvironmentByName('fake-environment-1')
        );

        static::assertNull($database->getEnvironmentByName('nothing'));
    }

    public function testItRetrievesAnEnvironmentByLocation(): void
    {
        file_put_contents($this->databasePath, $this->getFakeDatabaseContent());

        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        static::assertEquals(
            new EnvironmentEntity(
                'fake-environment-1',
                '/fake/location/1',
                'magento',
                'magento.localhost www.magento.localhost',
                false
            ),
            $database->getEnvironmentByLocation('/fake/location/1')
        );

        static::assertNull($database->getEnvironmentByLocation('nothing'));
    }

    public function testItAddsAnEnvironment(): void
    {
        file_put_contents($this->databasePath, $this->getFakeDatabaseContent());

        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        $environment = new EnvironmentEntity(
            'fake-environment-3',
            '/fake/location/3',
            'sylius',
            'sylius.localhost www.sylius.localhost',
            false
        );

        $database->add($environment);
        static::assertStringEqualsFile($this->databasePath, $this->getFakeDatabaseContent());

        $database->save();
        static::assertStringEqualsFile($this->databasePath, $this->getFakeDatabaseContentAfterAddition());
    }

    public function testItRemovesAnEnvironment(): void
    {
        file_put_contents($this->databasePath, $this->getFakeDatabaseContent());

        $fakeVariables = FakeVariables::fromArray(['HOME' => $this->location]);
        $database = new Database($fakeVariables);

        $environment = new EnvironmentEntity(
            'fake-environment-1',
            '/fake/location/1',
            'magento',
            'magento.localhost www.magento.localhost',
            false
        );

        $database->remove($environment);
        static::assertStringEqualsFile($this->databasePath, $this->getFakeDatabaseContent());

        $database->save();
        static::assertStringEqualsFile($this->databasePath, $this->getFakeDatabaseContentAfterDeletion());
    }

    /**
     * Retrieves the fake database content used to perform the tests.
     */
    private function getFakeDatabaseContent(): string
    {
        return <<<'EOT'
[
   {
      "name":"fake-environment-1",
      "location":"\/fake\/location\/1",
      "type":"magento",
      "domains":"magento.localhost www.magento.localhost",
      "active":false
   },
   {
      "name":"fake-environment-2",
      "location":"\/fake\/location\/2",
      "type":"symfony",
      "domains":"symfony.localhost www.symfony.localhost",
      "active":true
   }
]
EOT;
    }

    /**
     * Retrieves the fake database content once the addition test has been performed.
     */
    private function getFakeDatabaseContentAfterAddition(): string
    {
        return '[{"name":"fake-environment-1","location":"\/fake\/location\/1","type":"magento","domains":"magento.localhost www.magento.localhost","active":false},{"name":"fake-environment-2","location":"\/fake\/location\/2","type":"symfony","domains":"symfony.localhost www.symfony.localhost","active":true},{"name":"fake-environment-3","location":"\/fake\/location\/3","type":"sylius","domains":"sylius.localhost www.sylius.localhost","active":false}]';
    }

    /**
     * Retrieves the fake database content once the deletion test has been performed.
     */
    private function getFakeDatabaseContentAfterDeletion(): string
    {
        return '[{"name":"fake-environment-2","location":"\/fake\/location\/2","type":"symfony","domains":"symfony.localhost www.symfony.localhost","active":true}]';
    }
}
