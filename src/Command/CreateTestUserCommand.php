<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-user',
    description: 'Create a test user with API key',
)]
class CreateTestUserCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Create a test user with API key');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Verificar si el usuario ya existe
        $userRepository = $this->entityManager->getRepository(User::class);
        $existingUser = $userRepository->findOneBy(['email' => 'usuario@ejemplo.com']);

        if ($existingUser) {
            $io->note('El usuario de prueba ya existe.');
            $io->info(sprintf('Email: %s', $existingUser->getEmail()));
            $io->info(sprintf('API Key: %s', $existingUser->getApiKey()));
            return Command::SUCCESS;
        }

        // Crear nuevo usuario
        $user = new User();
        $user->setEmail('usuario@ejemplo.com');
        $user->setName('Usuario de Prueba');
        $user->setIsActive(true);
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        // La API Key se genera automáticamente en el constructor de User

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Usuario de prueba creado exitosamente!');
        $io->info(sprintf('Email: %s', $user->getEmail()));
        $io->info(sprintf('Roles: %s', implode(', ', $user->getRoles())));
        $io->warning('API Key generada (guárdala en lugar seguro):');
        $io->text($user->getApiKey());

        return Command::SUCCESS;
    }


}
