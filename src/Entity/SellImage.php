<?php

namespace App\Entity;

use App\Repository\SellImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Controller\SellImageFileController;

/**
 * @ORM\Entity(repositoryClass=SellImageRepository::class)
 * @Vich\Uploadable()
 */
#[ApiResource(
    collectionOperations: [
        'post' => [
            'normalization_context' => ['groups' => ['sellimage:read']],
            'denormalization_context' => ['groups' => ['sellimage:write']],
            'deserialize' => false,
            'controller' => SellImageFileController::class,
            "security" => "user.enabled == true"
        ]
    ],
    itemOperations: [
        'get' => [
            'normalization_context' => ['groups' => ['sellimage:read']],
            "security" => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_SALES') or is_granted('IS_ALLOWED', object)) and user.enabled == true"
        ],
        'delete' => [
            "security" => "(is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_SALES')) and user.enabled == true"
        ]
    ]
)]
class SellImage
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(["sell:read"])]
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(["sell:read", 'sellimage:write', 'sellimage:read'])]
    private $name;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[Groups(['sellimage:write', 'sellimage:read'])]
    private $createdDate;

    /**
     * @ORM\ManyToOne(targetEntity=Sell::class, inversedBy="images")
     * @ORM\JoinColumn(nullable=false)
     */
    #[Groups(['sellimage:write'])]
    private $sell;

    /**
     * @var File|null
     * @Vich\UploadableField(mapping="sell_image", fileNameProperty="name")
     */
    #[Groups(['sellimage:write'])]
    private $file;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->createdDate;
    }

    public function setCreatedDate(?\DateTimeInterface $createdDate): self
    {
        $this->createdDate = $createdDate;

        return $this;
    }

    public function getSell(): ?Sell
    {
        return $this->sell;
    }

    public function setSell(?Sell $sell): self
    {
        $this->sell = $sell;

        return $this;
    }

    /**
     * @return File|null
     */
    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * @param File|null
     * @return SellImage
     */
    public function setFile(?File $file): SellImage
    {
        $this->file = $file;
        return $this;
    }
}
