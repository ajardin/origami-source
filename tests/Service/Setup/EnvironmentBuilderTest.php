<?php

declare(strict_types=1);

namespace App\Tests\Service\Setup;

use App\Service\RequirementsChecker;
use App\Service\Setup\EnvironmentBuilder;
use App\Service\Setup\TechnologyIdentifier;
use App\Service\Setup\Validator;
use App\Service\Wrapper\OrigamiStyle;
use App\Service\Wrapper\ProcessProxy;
use App\Tests\TestEnvironmentTrait;
use App\ValueObject\PrepareAnswers;
use App\ValueObject\TechnologyDetails;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @internal
 *
 * @covers \App\Service\Setup\EnvironmentBuilder
 */
final class EnvironmentBuilderTest extends TestCase
{
    use ProphecyTrait;
    use TestEnvironmentTrait;

    private const TECHNOLOGY_REQUIREMENTS = [
        'symfony' => [
            '5.2' => [
                'database' => ['mariadb:10.5', 'mariadb:10.4', 'mariadb:10.3', 'mariadb:10.2', 'mariadb:10.1'],
                'php' => ['ajardin/php:8.0', 'ajardin/php:7.4', 'ajardin/php:7.3'],
            ],
        ],
    ];

    public function testItPreparesNewEnvironmentWithCertificates(): void
    {
        $processProxy = $this->prophesize(ProcessProxy::class);
        $technologyIdentifier = $this->prophesize(TechnologyIdentifier::class);
        $applicationRequirements = $this->prophesize(RequirementsChecker::class);
        $validator = $this->prophesize(Validator::class);
        $requirements = self::TECHNOLOGY_REQUIREMENTS;

        $io = $this->prophesize(OrigamiStyle::class);

        $processProxy
            ->getWorkingDirectory()
            ->shouldBeCalledOnce()
            ->willReturn('.')
        ;

        $technology = new TechnologyDetails('symfony', '5.2');

        $technologyIdentifier
            ->identify('.')
            ->shouldBeCalledOnce()
            ->willReturn($technology)
        ;

        // It should ask for the environment name.
        $io
            ->ask(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn('mysymfony')
        ;

        // It should ask for the environment type.
        $io
            ->choice(Argument::type('string'), Argument::type('array'), $technology->getName())
            ->shouldBeCalledOnce()
            ->willReturn('symfony')
        ;

        // It should ask for the environment version.
        $io
            ->choice(Argument::type('string'), Argument::type('array'), $technology->getVersion())
            ->shouldBeCalledOnce()
            ->willReturn('5.2')
        ;

        // It should ask for each environment setting.
        foreach ($requirements['symfony']['5.2'] as $name => $values) {
            $io
                ->choice(Argument::containingString($name), Argument::type('array'), Argument::type('string'))
                ->shouldBeCalledOnce()
                ->willReturn($values[0])
            ;
        }

        // It should ask for locally trusted certificates.
        $applicationRequirements
            ->canMakeLocallyTrustedCertificates()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;
        $io
            ->confirm(Argument::type('string'), false)
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;
        $io
            ->ask(Argument::type('string'), Argument::type('string'), Argument::type('closure'))
            ->shouldBeCalledOnce()
            ->willReturn('mydomain.test')
        ;

        $environmentBuilder = new EnvironmentBuilder(
            $processProxy->reveal(),
            $technologyIdentifier->reveal(),
            $applicationRequirements->reveal(),
            $validator->reveal(),
            $requirements
        );

        $defaultSettings = ['database' => 'mariadb:10.5', 'php' => 'ajardin/php:8.0'];
        $answers = new PrepareAnswers('mysymfony', '.', 'symfony', 'mydomain.test', $defaultSettings);

        static::assertSame(serialize($answers), serialize($environmentBuilder->prepare($io->reveal())));
    }

    public function testItPreparesNewEnvironmentWithoutCertificates(): void
    {
        $processProxy = $this->prophesize(ProcessProxy::class);
        $technologyIdentifier = $this->prophesize(TechnologyIdentifier::class);
        $applicationRequirements = $this->prophesize(RequirementsChecker::class);
        $validator = $this->prophesize(Validator::class);
        $requirements = self::TECHNOLOGY_REQUIREMENTS;

        $io = $this->prophesize(OrigamiStyle::class);

        $processProxy
            ->getWorkingDirectory()
            ->shouldBeCalledOnce()
            ->willReturn('.')
        ;

        $technology = new TechnologyDetails('symfony', '5.2');

        $technologyIdentifier
            ->identify('.')
            ->shouldBeCalledOnce()
            ->willReturn($technology)
        ;

        // It should ask for the environment name.
        $io
            ->ask(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledOnce()
            ->willReturn('mysymfony')
        ;

        // It should ask for the environment type.
        $io
            ->choice(Argument::type('string'), Argument::type('array'), $technology->getName())
            ->shouldBeCalledOnce()
            ->willReturn('symfony')
        ;

        // It should ask for the environment version.
        $io
            ->choice(Argument::type('string'), Argument::type('array'), $technology->getVersion())
            ->shouldBeCalledOnce()
            ->willReturn('5.2')
        ;

        // It should ask for each environment setting.
        foreach ($requirements['symfony']['5.2'] as $name => $values) {
            $io
                ->choice(Argument::containingString($name), Argument::type('array'), Argument::type('string'))
                ->shouldBeCalledOnce()
                ->willReturn($values[0])
            ;
        }

        // It should ask for locally trusted certificates.
        $applicationRequirements
            ->canMakeLocallyTrustedCertificates()
            ->shouldBeCalledOnce()
            ->willReturn(true)
        ;
        $io
            ->confirm(Argument::type('string'), false)
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;

        $environmentBuilder = new EnvironmentBuilder(
            $processProxy->reveal(),
            $technologyIdentifier->reveal(),
            $applicationRequirements->reveal(),
            $validator->reveal(),
            $requirements
        );

        $defaultSettings = ['database' => 'mariadb:10.5', 'php' => 'ajardin/php:8.0'];
        $answers = new PrepareAnswers('mysymfony', '.', 'symfony', null, $defaultSettings);

        static::assertSame(serialize($answers), serialize($environmentBuilder->prepare($io->reveal())));
    }

    public function testItPreparesNewEnvironmentWithoutMkcert(): void
    {
        $processProxy = $this->prophesize(ProcessProxy::class);
        $technologyIdentifier = $this->prophesize(TechnologyIdentifier::class);
        $applicationRequirements = $this->prophesize(RequirementsChecker::class);
        $validator = $this->prophesize(Validator::class);
        $requirements = self::TECHNOLOGY_REQUIREMENTS;

        $io = $this->prophesize(OrigamiStyle::class);

        $processProxy
            ->getWorkingDirectory()
            ->shouldBeCalledOnce()
            ->willReturn('.')
        ;

        $technology = new TechnologyDetails('symfony', '5.2');

        $technologyIdentifier
            ->identify('.')
            ->shouldBeCalledOnce()
            ->willReturn($technology)
        ;

        // It should ask for the environment name.
        $io
            ->ask(Argument::type('string'), Argument::type('string'))
            ->shouldBeCalledOnce()->willReturn('mysymfony')
        ;

        // It should ask for the environment type.
        $io
            ->choice(Argument::type('string'), Argument::type('array'), $technology->getName())
            ->shouldBeCalledOnce()
            ->willReturn('symfony')
        ;

        // It should ask for the environment version.
        $io
            ->choice(Argument::type('string'), Argument::type('array'), $technology->getVersion())
            ->shouldBeCalledOnce()
            ->willReturn('5.2')
        ;

        // It should ask for each environment setting.
        foreach ($requirements['symfony']['5.2'] as $name => $values) {
            $io
                ->choice(Argument::containingString($name), Argument::type('array'), Argument::type('string'))
                ->shouldBeCalledOnce()
                ->willReturn($values[0])
            ;
        }

        // It should ask for locally trusted certificates.
        $applicationRequirements
            ->canMakeLocallyTrustedCertificates()
            ->shouldBeCalledOnce()
            ->willReturn(false)
        ;
        $io
            ->warning(Argument::type('string'))
            ->shouldBeCalledOnce()
        ;

        $environmentBuilder = new EnvironmentBuilder(
            $processProxy->reveal(),
            $technologyIdentifier->reveal(),
            $applicationRequirements->reveal(),
            $validator->reveal(),
            $requirements
        );

        $defaultSettings = ['database' => 'mariadb:10.5', 'php' => 'ajardin/php:8.0'];
        $answers = new PrepareAnswers('mysymfony', '.', 'symfony', null, $defaultSettings);

        static::assertSame(serialize($answers), serialize($environmentBuilder->prepare($io->reveal())));
    }

    public function testItUpdatesExistingEnvironment(): void
    {
        $processProxy = $this->prophesize(ProcessProxy::class);
        $technologyIdentifier = $this->prophesize(TechnologyIdentifier::class);
        $applicationRequirements = $this->prophesize(RequirementsChecker::class);
        $validator = $this->prophesize(Validator::class);
        $requirements = self::TECHNOLOGY_REQUIREMENTS;

        $io = $this->prophesize(OrigamiStyle::class);
        $environment = $this->createEnvironment();

        $processProxy
            ->getWorkingDirectory()
            ->shouldBeCalledOnce()
            ->willReturn($environment->getLocation())
        ;

        $technology = new TechnologyDetails('symfony', '5.2');

        $technologyIdentifier
            ->identify($environment->getLocation())
            ->shouldBeCalledOnce()
            ->willReturn($technology)
        ;

        // It should not ask for the environment name.
        $io
            ->ask(Argument::type('string'), Argument::type('string'))
            ->shouldNotBeCalled()
        ;

        // It should not ask for the environment type.
        $io
            ->choice(Argument::type('string'), Argument::type('array'), $technology->getName())
            ->shouldNotBeCalled()
        ;

        // It should ask for the environment version.
        $io
            ->choice(Argument::type('string'), Argument::type('array'), $technology->getVersion())
            ->shouldBeCalledOnce()
            ->willReturn('5.2')
        ;

        // It should ask for each environment setting.
        foreach ($requirements['symfony']['5.2'] as $name => $values) {
            $io
                ->choice(Argument::containingString($name), Argument::type('array'), Argument::type('string'))
                ->shouldBeCalledOnce()
                ->willReturn($values[0])
            ;
        }

        // It should not ask for locally trusted certificates.
        $applicationRequirements
            ->canMakeLocallyTrustedCertificates()
            ->shouldNotBeCalled()
        ;

        $environmentBuilder = new EnvironmentBuilder(
            $processProxy->reveal(),
            $technologyIdentifier->reveal(),
            $applicationRequirements->reveal(),
            $validator->reveal(),
            $requirements
        );

        $defaultSettings = ['database' => 'mariadb:10.5', 'php' => 'ajardin/php:8.0'];
        $answers = new PrepareAnswers($environment->getName(), $environment->getLocation(), $environment->getType(), $environment->getDomains(), $defaultSettings);

        static::assertSame(serialize($answers), serialize($environmentBuilder->prepare($io->reveal(), $environment)));
    }
}
