<?php

declare(strict_types=1);

namespace App\Command\Main;

use App\Command\AbstractBaseCommand;
use App\Environment\EnvironmentEntity;
use App\Exception\InvalidConfigurationException;
use App\Exception\OrigamiExceptionInterface;
use App\Helper\CommandExitCode;
use App\Validator\Constraints\LocalDomains;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends AbstractBaseCommand
{
    /** @var array */
    private $availableTypes = [EnvironmentEntity::TYPE_MAGENTO2, EnvironmentEntity::TYPE_SYMFONY];

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Installs a Docker environment in the desired directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $type = $this->io->choice('Which type of environment you want to install?', $this->availableTypes);

            /** @var string $location */
            $location = realpath(
                $this->io->ask(
                    'Where do you want to install the environment?',
                    '.',
                    function ($answer) {
                        return $this->installationPathCallback($answer);
                    }
                )
            );

            if ($this->io->confirm('Do you want to generate a locally-trusted development certificate?', false)) {
                $domains = $this->io->ask(
                    'Which domains does this certificate belong to?',
                    sprintf('%s.localhost www.%s.localhost', $type, $type),
                    function ($answer) {
                        return $this->localDomainsCallback($answer);
                    }
                );
            } else {
                $domains = null;
            }

            $environment = $this->systemManager->install($location, $type, $domains);
            $this->database->add($environment);
            $this->database->save();

            $this->io->success('Environment successfully installed.');
        } catch (OrigamiExceptionInterface $exception) {
            $this->io->error($exception->getMessage());
            $exitCode = CommandExitCode::EXCEPTION;
        }

        return $exitCode ?? CommandExitCode::SUCCESS;
    }

    /**
     * Validates the response provided by the user to the installation path question.
     *
     * @throws InvalidConfigurationException
     */
    private function installationPathCallback(string $answer): string
    {
        if (!is_dir($answer)) {
            throw new InvalidConfigurationException('An existing directory must be provided.');
        }

        return $answer;
    }

    /**
     * Validates the response provided by the user to the local domains question.
     *
     * @throws InvalidConfigurationException
     */
    private function localDomainsCallback(string $answer): string
    {
        $constraint = new LocalDomains();
        $errors = $this->validator->validate($answer, $constraint);
        if ($errors->has(0)) {
            /** @var string $message */
            $message = $errors->get(0)->getMessage();

            throw new InvalidConfigurationException($message);
        }

        return $answer;
    }
}
