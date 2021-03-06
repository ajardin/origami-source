<?php

declare(strict_types=1);

namespace App\Service\Setup;

use App\ValueObject\EnvironmentEntity;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Validator\Constraints\Hostname;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class Validator
{
    private ValidatorInterface $symfonyValidator;
    private string $projectDir;
    private string $installDir;

    public function __construct(ValidatorInterface $symfonyValidator, string $projectDir, string $installDir)
    {
        $this->symfonyValidator = $symfonyValidator;
        $this->projectDir = $projectDir;
        $this->installDir = $installDir;
    }

    /**
     * Checks whether the environment has a dotenv file.
     */
    public function validateDotEnvExistence(EnvironmentEntity $environment): bool
    {
        $filesystem = new Filesystem();
        $configuration = $environment->getLocation().$this->installDir.'/.env';

        return $filesystem->exists($configuration);
    }

    /**
     * Checks whether the environment configuration is correctly installed.
     */
    public function validateConfigurationFiles(EnvironmentEntity $environment): bool
    {
        $filesystem = new Filesystem();

        $finder = new Finder();
        $finder->files()->in($this->projectDir."/src/Resources/{$environment->getType()}")->depth(0);

        foreach ($finder as $file) {
            $filename = str_replace(
                'custom-',
                '',
                $environment->getLocation().$this->installDir.'/'.$file->getFilename(),
            );

            if (!$filesystem->exists($filename)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks whether the given hostname is valid for local use.
     */
    public function validateHostname(string $hostname): bool
    {
        $constraint = new Hostname(['requireTld' => false]);
        $errors = $this->symfonyValidator->validate($hostname, $constraint);

        return !$errors->has(0);
    }
}
