<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class PasskeyCredential
{
    private ?int $id = null;

    private int $userId;

    private string $credentialIdHash;

    private string $credentialId;

    private string $userHandle;

    private string $credentialRecord;

    private \DateTimeImmutable $createdAt;

    private ?\DateTimeImmutable $lastUsedAt = null;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('plugin_mautic_passkey_credentials')
            ->setCustomRepositoryClass(PasskeyCredentialRepository::class)
            ->addUniqueConstraint(['credential_id_hash'], 'passkey_credential_hash_unique')
            ->addIndex(['user_id'], 'passkey_user_id');

        $builder->addId();
        $builder->createField('userId', Types::INTEGER)
            ->columnName('user_id')
            ->option('unsigned', true)
            ->build();
        $builder->createField('credentialIdHash', Types::STRING)
            ->columnName('credential_id_hash')
            ->length(64)
            ->build();
        $builder->createField('credentialId', Types::TEXT)
            ->columnName('credential_id')
            ->build();
        $builder->createField('userHandle', Types::STRING)
            ->columnName('user_handle')
            ->length(128)
            ->build();
        $builder->createField('credentialRecord', Types::TEXT)
            ->columnName('credential_record')
            ->build();
        $builder->createField('createdAt', Types::DATETIME_IMMUTABLE)
            ->columnName('created_at')
            ->build();
        $builder->createField('lastUsedAt', Types::DATETIME_IMMUTABLE)
            ->columnName('last_used_at')
            ->nullable()
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCredentialIdHash(): string
    {
        return $this->credentialIdHash;
    }

    public function setCredentialIdHash(string $credentialIdHash): self
    {
        $this->credentialIdHash = $credentialIdHash;

        return $this;
    }

    public function getCredentialId(): string
    {
        return $this->credentialId;
    }

    public function setCredentialId(string $credentialId): self
    {
        $this->credentialId = $credentialId;

        return $this;
    }

    public function getUserHandle(): string
    {
        return $this->userHandle;
    }

    public function setUserHandle(string $userHandle): self
    {
        $this->userHandle = $userHandle;

        return $this;
    }

    public function getCredentialRecord(): string
    {
        return $this->credentialRecord;
    }

    public function setCredentialRecord(string $credentialRecord): self
    {
        $this->credentialRecord = $credentialRecord;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeImmutable $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;

        return $this;
    }
}

