<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Environment\EnvironmentEntity;
use App\Exception\InvalidEnvironmentException;
use App\Helper\ProcessFactory;
use App\Middleware\Binary\Mkcert;
use Symfony\Component\Filesystem\Filesystem;

class SystemManager
{
    /** @var Mkcert */
    private $mkcert;

    /** @var ProcessFactory */
    private $processFactory;

    public function __construct(Mkcert $mkcert, ProcessFactory $processFactory)
    {
        $this->mkcert = $mkcert;
        $this->processFactory = $processFactory;
    }

    /**
     * Installs the Docker environment configuration.
     *
     * @throws InvalidEnvironmentException
     */
    public function install(string $location, string $type, ?string $domains = null): EnvironmentEntity
    {
        if ($type !== EnvironmentEntity::TYPE_CUSTOM) {
            $source = __DIR__.sprintf('/../Resources/%s', $type);
            $destination = sprintf('%s/var/docker', $location);

            $filesystem = new Filesystem();
            $this->copyEnvironmentFiles($filesystem, $source, $destination);

            if ($domains !== null) {
                $certificate = sprintf('%s/nginx/certs/custom.pem', $destination);
                $privateKey = sprintf('%s/nginx/certs/custom.key', $destination);

                $this->mkcert->generateCertificate($certificate, $privateKey, explode(' ', $domains));
            }
        }

        return new EnvironmentEntity(basename($location), $location, $type, $domains);
    }

    /**
     * Uninstalls the Docker environment configuration.
     */
    public function uninstall(EnvironmentEntity $environment): void
    {
        if ($environment->getType() !== EnvironmentEntity::TYPE_CUSTOM) {
            $filesystem = new Filesystem();
            $filesystem->remove(sprintf('%s/var/docker', $environment->getLocation()));
        }
    }

    /**
     * Checks whether the given binary is available.
     */
    public function isBinaryInstalled(string $binary): bool
    {
        return $this->processFactory->runBackgroundProcess(['which', $binary])->isSuccessful();
    }

    /**
     * Prepare the project directory with environment files.
     */
    private function copyEnvironmentFiles(Filesystem $filesystem, string $source, string $destination): void
    {
        // Create the directory where all configuration files will be stored
        $filesystem->mkdir($destination);

        // Copy the environment files into the project directory
        $filesystem->mirror($source, $destination);
    }
}
