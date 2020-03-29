<?php

declare(strict_types=1);

namespace App\Tests\Command\Additional;

use App\Command\Additional\RegisterCommand;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
use App\Helper\CommandExitCode;
use App\Tests\AbstractCommandWebTestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @covers \App\Command\AbstractBaseCommand
 * @covers \App\Command\Additional\RegisterCommand
 */
final class RegisterCommandTest extends AbstractCommandWebTestCase
{
    public function testItRegistersAnExternalEnvironment(): void
    {
        $this->processProxy->getWorkingDirectory()->shouldBeCalledOnce()->willReturn('');
        $this->systemManager->install('', EnvironmentEntity::TYPE_CUSTOM, null)->shouldBeCalledOnce();

        $commandTester = new CommandTester($this->getCommand(RegisterCommand::class));
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[OK] Environment successfully registered.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItAbortsTheRegistrationAfterDisapproval(): void
    {
        $this->processProxy->getWorkingDirectory()->shouldNotBeCalled();
        $this->systemManager->install(Argument::type('string'), EnvironmentEntity::TYPE_CUSTOM, null)->shouldNotBeCalled();

        $commandTester = new CommandTester($this->getCommand(RegisterCommand::class));
        $commandTester->setInputs(['no']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringNotContainsString('[OK] Environment successfully registered.', $display);
        static::assertSame(CommandExitCode::SUCCESS, $commandTester->getStatusCode());
    }

    public function testItGracefullyExitsWhenAnExceptionOccurred(): void
    {
        $this->processProxy->getWorkingDirectory()
            ->shouldBeCalledOnce()
            ->willThrow(new InvalidEnvironmentException('Unable to determine the current working directory.'))
        ;
        $this->systemManager->install(Argument::type('string'), EnvironmentEntity::TYPE_CUSTOM, null)->shouldNotBeCalled();

        $commandTester = new CommandTester($this->getCommand(RegisterCommand::class));
        $commandTester->setInputs(['yes']);
        $commandTester->execute([]);

        $display = $commandTester->getDisplay();
        static::assertStringContainsString('[ERROR] Unable to determine the current working directory.', $display);
        static::assertSame(CommandExitCode::EXCEPTION, $commandTester->getStatusCode());
    }
}
