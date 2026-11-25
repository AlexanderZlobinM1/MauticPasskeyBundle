<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\Service;

use Symfony\Component\HttpFoundation\Request;

final class PasskeyChallengeStore
{
    public const ASSERTION = 'assertion';
    public const REGISTRATION = 'registration';

    private const SESSION_PREFIX = 'mautic_passkey_ceremony_';
    private const MAX_AGE_SECONDS = 300;

    /** @param array<string, mixed> $context */
    public function put(Request $request, string $kind, array $context): void
    {
        $this->assertKind($kind);
        $context['kind']      = $kind;
        $context['issued_at'] = time();
        $request->getSession()->set(self::SESSION_PREFIX.$kind, $context);
    }

    /** @return array<string, mixed> */
    public function consume(Request $request, string $kind): array
    {
        $this->assertKind($kind);
        $key     = self::SESSION_PREFIX.$kind;
        $session = $request->getSession();
        $context = $session->get($key);
        $session->remove($key);

        if (!is_array($context)
            || ($context['kind'] ?? null) !== $kind
            || !isset($context['issued_at'])
            || time() - (int) $context['issued_at'] > self::MAX_AGE_SECONDS
        ) {
            throw new \RuntimeException('The passkey ceremony is missing or expired.');
        }

        $expectedOrigin = $request->getSchemeAndHttpHost();
        if (!isset($context['origin']) || !hash_equals((string) $context['origin'], $expectedOrigin)) {
            throw new \RuntimeException('The passkey origin changed during authentication.');
        }

        if (!isset($context['rp_id']) || !hash_equals((string) $context['rp_id'], $request->getHost())) {
            throw new \RuntimeException('The passkey relying party changed during authentication.');
        }

        return $context;
    }

    private function assertKind(string $kind): void
    {
        if (!in_array($kind, [self::ASSERTION, self::REGISTRATION], true)) {
            throw new \InvalidArgumentException('Unsupported passkey ceremony.');
        }
    }
}

