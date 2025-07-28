<?php

namespace App\Command;

use App\Entity\User;
use App\Service\ApiKeyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:regenerate-api-key',
    description: 'Regenerate API key for a user',
)]
class RegenerateApiKeyCommand extends Command
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
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email')
            ->setDescription('Regenerate API key for a user')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            $io->error(sprintf('Usuario con email "%s" no encontrado.', $email));
            return Command::FAILURE;
        }

        $newApiKey = $this->apiKeyService->regenerateUserApiKey($user);

        $io->success('API Key regenerada exitosamente!');
        $io->info(sprintf('Usuario: %s', $user->getEmail()));
        $io->warning('Nueva API Key (guárdala en lugar seguro):');
        $io->text($newApiKey);
        $io->note('La API key anterior ya no es válida.');

        return Command::SUCCESS;
    }
}
