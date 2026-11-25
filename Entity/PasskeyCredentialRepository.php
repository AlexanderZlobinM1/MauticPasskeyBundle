<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\Entity;

use Mautic\CoreBundle\Entity\CommonRepository;

final class PasskeyCredentialRepository extends CommonRepository
{
    public function findByRawCredentialId(string $credentialId): ?PasskeyCredential
    {
        $credential = $this->findOneBy(['credentialIdHash' => hash('sha256', $credentialId)]);

        return $credential instanceof PasskeyCredential ? $credential : null;
    }

    public function findFirstByUserId(int $userId): ?PasskeyCredential
    {
        $credential = $this->findOneBy(['userId' => $userId], ['id' => 'ASC']);

        return $credential instanceof PasskeyCredential ? $credential : null;
    }

    /** @return PasskeyCredential[] */
    public function findByUserId(int $userId): array
    {
        return $this->findBy(['userId' => $userId], ['id' => 'ASC']);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @param int[] $userIds @return array<int, int> */
    public function countByUserIds(array $userIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ([] === $ids) {
            return [];
        }

        $rows = $this->createQueryBuilder('p')
            ->select('p.userId AS user_id, COUNT(p.id) AS credential_count')
            ->where('p.userId IN (:ids)')
            ->groupBy('p.userId')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['user_id']] = (int) $row['credential_count'];
        }

        return $counts;
    }

    public function deleteByUserId(int $userId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->delete()
            ->where('p.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }
}

