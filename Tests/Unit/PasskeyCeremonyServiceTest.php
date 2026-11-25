<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\Tests\Unit;

use MauticPlugin\MauticPasskeyBundle\Service\PasskeyCeremonyService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class PasskeyCeremonyServiceTest extends TestCase
{
    public function testAssertionOptionsAreDiscoverableAndRequireUserVerification(): void
    {
        $result = (new PasskeyCeremonyService())->createAssertionOptions(
            Request::create('https://mautic.example.test/login')
        );
        $json = json_decode($result['json'], true, 32, JSON_THROW_ON_ERROR);

        self::assertSame('mautic.example.test', $result['rp_id']);
        self::assertSame('https://mautic.example.test', $result['origin']);
        self::assertSame('required', $json['userVerification']);
        self::assertSame([], $json['allowCredentials']);
        self::assertNotEmpty($json['challenge']);
    }

    public function testInsecureNonLocalOriginIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        (new PasskeyCeremonyService())->createAssertionOptions(
            Request::create('http://mautic.example.test/login')
        );
    }

    public function testLocalhostIsAllowedForDevelopmentFixtures(): void
    {
        $result = (new PasskeyCeremonyService())->createAssertionOptions(
            Request::create('http://localhost/login')
        );

        self::assertSame('localhost', $result['rp_id']);
    }
}
