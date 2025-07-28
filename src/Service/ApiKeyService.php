<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/*
  Servicio para gestión segura de API Keys
 */
class ApiKeyService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Genera una API Key criptográficamente segura
     */
    public function generateApiKey(): string
    {
        do {
            // Generar 32 bytes aleatorios (256 bits)
            $randomBytes = random_bytes(32);
            $apiKey = bin2hex($randomBytes);
            
            // Verificar que no exista en la base de datos
            $existingUser = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['apiKey' => $apiKey]);
                
        } while ($existingUser !== null);

        return $apiKey;
    }

    /*
      Hashea una API Key para almacenamiento seguro
     */
    public function hashApiKey(string $apiKey): string
    {
        return password_hash($apiKey, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iteraciones
            'threads' => 3,         // 3 threads
        ]);
    }

    /*
      Verifica una API Key contra su hash
     */
    public function verifyApiKey(string $apiKey, string $hashedApiKey): bool
    {
        return password_verify($apiKey, $hashedApiKey);
    }

    /*
      Regenera la API Key de un usuario
     */
    public function regenerateUserApiKey(User $user): string
    {
        $newApiKey = $this->generateApiKey();
        $hashedApiKey = $this->hashApiKey($newApiKey);
        
        $user->setApiKey($hashedApiKey);
        $this->entityManager->flush();
        
        return $newApiKey; // Retorna la key sin hashear para mostrar al usuario
    }

    /*
      Valida el formato de una API Key
     */
    public function isValidApiKeyFormat(string $apiKey): bool
    {
        // Debe ser exactamente 64 caracteres hexadecimales
        return preg_match('/^[a-f0-9]{64}$/', $apiKey) === 1;
    }
}
