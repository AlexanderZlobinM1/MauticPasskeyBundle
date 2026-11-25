<?php

declare(strict_types=1);

namespace MauticPlugin\MauticPasskeyBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\UserBundle\Entity\UserRepository;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredential;
use MauticPlugin\MauticPasskeyBundle\Entity\PasskeyCredentialRepository;
use MauticPlugin\MauticPasskeyBundle\Integration\MauticPasskeyIntegration;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class UserPasskeyController extends CommonController
{
    public function clearAction(
        Request $request,
        IntegrationHelper $integrationHelper,
        UserHelper $userHelper,
        CorePermissions $permissions,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        $returnUrl   = $this->generateUrl('mautic_user_index');
        $integration = $integrationHelper->getIntegrationObject(MauticPasskeyIntegration::NAME);
        $targetId    = (int) $request->request->get('user_id', 0);
        $target      = $targetId > 0 ? $userRepository->find($targetId) : null;
        $current     = $userHelper->getUser();

        if (!$integration instanceof MauticPasskeyIntegration
            || !$integration->getIntegrationSettings()->isPublished()
            || !$target
            || !$current
        ) {
            return new RedirectResponse($returnUrl);
        }

        $permission = $targetId === (int) $current->getId()
            ? 'user:users:editown'
            : 'user:users:editother';

        if (!$this->isCsrfTokenValid('mautic_passkey_user_clear', (string) $request->request->get('_token'))
            || !$permissions->isGranted($permission)
        ) {
            throw new AccessDeniedHttpException();
        }

        $repository = $entityManager->getRepository(PasskeyCredential::class);
        if ($repository instanceof PasskeyCredentialRepository) {
            $repository->deleteByUserId($targetId);
        }

        $this->addFlash(FlashBag::LEVEL_SUCCESS, $this->translator->trans(
            'mautic.passkey.clear.success',
            ['%user%' => $target->getName(true)]
        ));

        return new RedirectResponse($returnUrl);
    }
}

