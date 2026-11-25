<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredential;

final class Version_1_0_0 extends AbstractMigration
{
    private ?ClassMetadata $metadata = null;

    protected function isApplicable(Schema $schema): bool
    {
        $this->metadata = $this->entityManager->getClassMetadata(PasskeyCredential::class);

        return !$schema->hasTable($this->metadata->getTableName());
    }

    protected function up(): void
    {
        if (!$this->metadata) {
            return;
        }

        foreach ((new SchemaTool($this->entityManager))->getCreateSchemaSql([$this->metadata]) as $sql) {
            $this->addSql($sql);
        }
    }
}

