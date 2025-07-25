<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('La cuenta de usuario está desactivada.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Aquí puedes agregar validaciones adicionales después de la autenticación
        // Por ejemplo, verificar si el usuario necesita cambiar su API Key
    }
}
