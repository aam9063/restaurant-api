<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Restaurants\Repository\RestaurantRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
#[ORM\Table(name: 'restaurants')]
#[ApiResource(
    operations: [
        new GetCollection(
            normalizationContext: ['groups' => ['restaurant:read']],
            security: "is_granted('ROLE_USER')"
        ),
        new Post(
            normalizationContext: ['groups' => ['restaurant:read']],
            denormalizationContext: ['groups' => ['restaurant:write']],
            security: "is_granted('ROLE_USER')"
        ),
        new Get(
            normalizationContext: ['groups' => ['restaurant:read']],
            security: "is_granted('ROLE_USER')"
        ),
        new Put(
            normalizationContext: ['groups' => ['restaurant:read']],
            denormalizationContext: ['groups' => ['restaurant:write']],
            security: "is_granted('ROLE_USER')"
        ),
        new Patch(
            normalizationContext: ['groups' => ['restaurant:read']],
            denormalizationContext: ['groups' => ['restaurant:write']],
            security: "is_granted('ROLE_USER')"
        ),
        new Delete(
            security: "is_granted('ROLE_USER')"
        ),
    ],
    normalizationContext: ['groups' => ['restaurant:read']],
    denormalizationContext: ['groups' => ['restaurant:write']],
    paginationEnabled: true,
    paginationItemsPerPage: 10
)]
class Restaurant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['restaurant:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'El nombre del restaurante es obligatorio')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'El nombre debe tener al menos {{ limit }} caracteres',
        maxMessage: 'El nombre no puede tener más de {{ limit }} caracteres'
    )]
    #[Groups(['restaurant:read', 'restaurant:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank(message: 'La dirección es obligatoria')]
    #[Assert\Length(
        min: 5,
        max: 500,
        minMessage: 'La dirección debe tener al menos {{ limit }} caracteres',
        maxMessage: 'La dirección no puede tener más de {{ limit }} caracteres'
    )]
    #[Groups(['restaurant:read', 'restaurant:write'])]
    private ?string $address = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'El teléfono es obligatorio')]
    #[Assert\Regex(
        pattern: '/^[\+]?[0-9\s\-\(\)]+$/',
        message: 'El formato del teléfono no es válido'
    )]
    #[Assert\Length(
        min: 9,
        max: 20,
        minMessage: 'El teléfono debe tener al menos {{ limit }} caracteres',
        maxMessage: 'El teléfono no puede tener más de {{ limit }} caracteres'
    )]
    #[Groups(['restaurant:read', 'restaurant:write'])]
    private ?string $phone = null;

    #[ORM\Column]
    #[Groups(['restaurant:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['restaurant:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
