<?php

/*
 * This file is part of monofony.
 *
 * (c) Mobizel
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ResetPassword;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\User\Model\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Webmozart\Assert\Assert;

final class ResetPasswordHandler implements MessageHandlerInterface
{
    private $appUserRepository;
    private $entityManager;
    private $requestStack;
    private $syliusResources;

    public function __construct(
        RepositoryInterface $appUserRepository,
        EntityManagerInterface $entityManager,
        RequestStack $requestStack,
        array $syliusResources
    ) {
        $this->appUserRepository = $appUserRepository;
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->syliusResources = $syliusResources;
    }

    public function __invoke(ResetPassword $message): void
    {
        /** @var UserInterface $user */
        $user = $this->appUserRepository->findOneBy(['passwordResetToken' => $this->getToken()]);

        if (null === $user) {
            throw new NotFoundHttpException('Token not found.');
        }

        $lifetime = new \DateInterval($this->getTtl());
        if (!$user->isPasswordRequestNonExpired($lifetime)) {
            $this->handleExpiredToken($user);

            return;
        }

        $user->setPlainPassword($message->password);
        $user->setPasswordResetToken(null);
        $user->setPasswordRequestedAt(null);

        $this->entityManager->flush();
    }

    private function getToken(): string
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();
        Assert::notNull($request);

        /** @var string $token */
        $token = $request->get('token');
        Assert::notNull($token);

        return $token;
    }

    private function getTtl(): string
    {
        /** @var string $ttl */
        $ttl = $this->syliusResources['sylius.app_user']['resetting']['token']['ttl'] ?? null;
        Assert::notNull($ttl, 'Token ttl was not found but it should.');

        return $ttl;
    }

    protected function handleExpiredToken(UserInterface $user): void
    {
        $user->setPasswordResetToken(null);
        $user->setPasswordRequestedAt(null);

        $this->entityManager->flush();

        throw new BadRequestHttpException('Token expired.');
    }
}
