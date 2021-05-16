<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ApplicationRequirements;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Process\ExecutableFinder;

/**
 * @internal
 *
 * @covers \App\Service\ApplicationRequirements
 */
final class RequirementsCheckerTest extends TestCase
{
    use ProphecyTrait;

    public function testItDetectsMandatoryBinaryStatus(): void
    {
        $executableFinder = $this->prophesize(ExecutableFinder::class);

        $executableFinder
            ->find('docker')
            ->shouldBeCalledOnce()
            ->willReturn('/usr/local/bin/docker')
        ;

        $executableFinder
            ->find('mutagen')
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        $requirementsChecker = new ApplicationRequirements($executableFinder->reveal());
        static::assertSame([
            [
                'name' => 'docker',
                'description' => 'A self-sufficient runtime for containers.',
                'status' => true,
            ],
            [
                'name' => 'mutagen',
                'description' => 'Fast and efficient way to synchronize code to Docker containers.',
                'status' => false,
            ],
        ], $requirementsChecker->checkMandatoryRequirements());
    }

    public function testItDetectsCertificatesBinaryFoundStatus(): void
    {
        $executableFinder = $this->prophesize(ExecutableFinder::class);

        $executableFinder
            ->find('mkcert')
            ->shouldBeCalledOnce()
            ->willReturn('/usr/local/bin/mkcert')
        ;

        $requirementsChecker = new ApplicationRequirements($executableFinder->reveal());
        static::assertSame([
            [
                'name' => 'mkcert',
                'description' => 'A simple zero-config tool to make locally trusted development certificates.',
                'status' => true,
            ],
        ], $requirementsChecker->checkNonMandatoryRequirements());
    }

    public function testItDetectsCertificatesBinaryNotFoundStatus(): void
    {
        $executableFinder = $this->prophesize(ExecutableFinder::class);

        $executableFinder
            ->find('mkcert')
            ->shouldBeCalledOnce()
            ->willReturn(null)
        ;

        $requirementsChecker = new ApplicationRequirements($executableFinder->reveal());
        static::assertSame([
            [
                'name' => 'mkcert',
                'description' => 'A simple zero-config tool to make locally trusted development certificates.',
                'status' => false,
            ],
        ], $requirementsChecker->checkNonMandatoryRequirements());
    }
}