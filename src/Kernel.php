<?php

namespace App;

use App\Exception\ConfigurationException;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /**
     * {@inheritdoc}
     */
    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', true);
        $confDir = $this->getProjectDir().'/config';

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        $routes->import($confDir.'/{routes}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');
    }

    /**
     * {@inheritdoc}
     *
     * @throws ConfigurationException
     */
    public function getCacheDir()
    {
        return strpos($this->getProjectDir(), 'phar://') !== false
            ? "{$this->getCustomDir()}/cache" : parent::getCacheDir();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ConfigurationException
     */
    public function getLogDir()
    {
        return strpos($this->getProjectDir(), 'phar://') !== false
            ? "{$this->getCustomDir()}/log" : parent::getCacheDir();
    }

    /**
     * TODO: improve this check (must work on multiple OS).
     *
     * @throws ConfigurationException
     *
     * @return string
     */
    private function getCustomDir(): string
    {
        if (!$home = getenv('HOME')) {
            throw new ConfigurationException('Unable to determine the home directory.');
        }

        $customDir = "${home}/.origami";
        if (!is_dir(\dirname($customDir))) {
            mkdir(\dirname($customDir), 0777, true);
        }

        return $customDir;
    }
}
