<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-user-roles',
    description: 'Update user roles',
)]
class UpdateUserRolesCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->addArgument('roles', InputArgument::REQUIRED, 'Comma-separated roles (e.g., ROLE_USER,ROLE_ADMIN)')
            ->setDescription('Update user roles')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $rolesString = $input->getArgument('roles');

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('Usuario con email "%s" no encontrado.', $email));
            return Command::FAILURE;
        }

        $roles = array_map('trim', explode(',', $rolesString));
        $roles = array_filter($roles);

        if (empty($roles)) {
            $io->error('Debe especificar al menos un rol.');
            return Command::FAILURE;
        }

        $user->setRoles($roles);
        $this->entityManager->flush();

        $io->success('Roles actualizados exitosamente!');
        $io->info(sprintf('Usuario: %s', $user->getEmail()));
        $io->info(sprintf('Nuevos roles: %s', implode(', ', $user->getRoles())));

        return Command::SUCCESS;
    }
}
