<?php

declare(strict_types=1);

namespace App\Tests\Command\Contextual;

use App\Command\Contextual\LogsCommand;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Tests\AbstractCommandWebTestCase;
use App\Tests\TestFakeEnvironmentTrait;
use Generator;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Contextual\LogsCommand
 *
 * @uses \App\Event\AbstractEnvironmentEvent
 */
final class LogsCommandTest extends AbstractCommandWebTestCase
{
    use TestFakeEnvironmentTrait;

    /**
     * @dataProvider provideCommandModifiers
     *
     * @throws InvalidEnvironmentException
     */
    public function testItShowsServicesLogs(?int $tail, ?string $service): void
    {
        $environment = $this->getFakeEnvironment();

        $this->database->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->showServicesLogs($tail ?? 0, $service)->shouldBeCalledOnce();

        $commandTester = new CommandTester($this->getCommand(LogsCommand::class));
        $commandTester->execute(
            ['--tail' => $tail, 'service' => $service],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        $display = $commandTester->getDisplay();

        static::assertDisplayIsVerbose($environment, $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * @dataProvider provideCommandModifiers
     *
     * @throws InvalidEnvironmentException
     */
    public function testItGracefullyExitsWhenAnExceptionOccurred(?int $tail, ?string $service): void
    {
        $environment = $this->getFakeEnvironment();

        $this->database->getActiveEnvironment()->shouldBeCalledOnce()->willReturn($environment);
        $this->dockerCompose->setActiveEnvironment($environment)->shouldBeCalledOnce();
        $this->dockerCompose->showServicesLogs($tail ?? 0, $service)
            ->willThrow(new InvalidEnvironmentException('Dummy exception.'))
        ;

        $commandTester = new CommandTester($this->getCommand(LogsCommand::class));
        $commandTester->execute(
            ['--tail' => $tail, 'service' => $service],
            ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]
        );

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Dummy exception.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }

    public function provideCommandModifiers(): Generator
    {
        yield [null, null];
        yield [50, null];
        yield [50, 'php'];
        yield [null, 'php'];
    }
}
