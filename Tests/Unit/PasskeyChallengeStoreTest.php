<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\Tests\Unit;

use MauticPlugin\MauticPasskeyBundle\Service\PasskeyChallengeStore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class PasskeyChallengeStoreTest extends TestCase
{
    public function testContextIsBoundToOriginAndConsumedOnce(): void
    {
        $store   = new PasskeyChallengeStore();
        $request = Request::create('https://mautic.example.test/login');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $store->put($request, PasskeyChallengeStore::ASSERTION, [
            'origin' => 'https://mautic.example.test',
            'rp_id'  => 'mautic.example.test',
            'value'  => 'proof',
        ]);

        self::assertSame('proof', $store->consume($request, PasskeyChallengeStore::ASSERTION)['value']);

        $this->expectException(\RuntimeException::class);
        $store->consume($request, PasskeyChallengeStore::ASSERTION);
    }

    public function testChangedOriginIsRejectedAndStillConsumed(): void
    {
        $store   = new PasskeyChallengeStore();
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('https://mautic.example.test/login');
        $request->setSession($session);
        $store->put($request, PasskeyChallengeStore::REGISTRATION, [
            'origin' => 'https://mautic.example.test',
            'rp_id'  => 'mautic.example.test',
        ]);

        $foreign = Request::create('https://other.example.test/login');
        $foreign->setSession($session);

        try {
            $store->consume($foreign, PasskeyChallengeStore::REGISTRATION);
            self::fail('A changed WebAuthn origin must be rejected.');
        } catch (\RuntimeException) {
            self::assertFalse($session->has('mautic_passkey_ceremony_registration'));
        }
    }

    public function testExpiredContextIsRejected(): void
    {
        $store   = new PasskeyChallengeStore();
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('https://mautic.example.test/login');
        $request->setSession($session);
        $session->set('mautic_passkey_ceremony_assertion', [
            'kind'      => PasskeyChallengeStore::ASSERTION,
            'issued_at' => time() - 301,
            'origin'    => 'https://mautic.example.test',
            'rp_id'     => 'mautic.example.test',
        ]);

        $this->expectException(\RuntimeException::class);
        $store->consume($request, PasskeyChallengeStore::ASSERTION);
    }
}
