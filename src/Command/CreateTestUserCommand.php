<?php

namespace App\Command;

use App\Entity\User;
use App\Service\ApiKeyService;
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
    private ApiKeyService $apiKeyService;

    public function __construct(EntityManagerInterface $entityManager, ApiKeyService $apiKeyService)
    {
        $this->entityManager = $entityManager;
        $this->apiKeyService = $apiKeyService;
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

        // Generar API Key segura
        $apiKey = $this->apiKeyService->generateApiKey();
        $hashedApiKey = $this->apiKeyService->hashApiKey($apiKey);

        // Crear nuevo usuario
        $user = new User();
        $user->setEmail('usuario@ejemplo.com');
        $user->setName('Usuario de Prueba');
        $user->setApiKey($hashedApiKey); // Almacenar hasheada
        $user->setIsActive(true);
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']); // Usuario con permisos de admin

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Usuario de prueba creado exitosamente!');
        $io->info(sprintf('Email: %s', $user->getEmail()));
        $io->info(sprintf('Roles: %s', implode(', ', $user->getRoles())));
        $io->warning('API Key generada (guárdala en lugar seguro):');
        $io->text($apiKey); // Mostrar solo una vez

        return Command::SUCCESS;
    }


}
