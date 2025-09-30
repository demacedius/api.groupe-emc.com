<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\WorkSheetRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=WorkSheetRepository::class)
 */
#[ApiResource(
    collectionOperations: [
        'post' => [
            "security_post_denormalize" => "(is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_SALES') or (is_granted('ROLE_SALES') and is_granted('IS_ALLOWED', object))) and user.enabled == true"
        ]
    ],
    itemOperations: [
        'get' => [
            "security" => "(is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_SALES')) and user.enabled == true"
        ],
        'patch' => [
            "security_post_denormalize" => "(is_granted('ROLE_SUPER_ADMIN') or is_granted('ROLE_ADMIN') or is_granted('ROLE_SUPER_SALES') or (is_granted('ROLE_SALES') and is_granted('IS_ALLOWED', object))) and user.enabled == true"
        ]
    ]
)]
class WorkSheet
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    #[Groups(["sell:read"])]
    private $id;

    /**
     * @ORM\OneToOne(targetEntity=Sell::class, inversedBy="workSheet", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $sell;

    /**
     * @ORM\ManyToOne(targetEntity=ServiceCategory::class)
     * @ORM\JoinColumn(nullable=false)
     */
    #[Groups(["sell:read"])]
    private $serviceCategory;

    /**
     * @ORM\Column(type="text")
     */
    #[Groups(["sell:read"])]
    private $task;

    /**
     * @ORM\Column(type="integer")
     */#[Groups(["sell:read"])]
    private $surface;

    /**
     * @ORM\Column(type="json")
     */
    #[Groups(["sell:read"])]
    private $content = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Groups(["sell:read"])]
    private $accessibility;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    #[Groups(["sell:read"])]
    private $comment;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSell(): ?Sell
    {
        return $this->sell;
    }

    public function setSell(Sell $sell): self
    {
        $this->sell = $sell;

        return $this;
    }

    public function getServiceCategory(): ?ServiceCategory
    {
        return $this->serviceCategory;
    }

    public function setServiceCategory(?ServiceCategory $serviceCategory): self
    {
        $this->serviceCategory = $serviceCategory;

        return $this;
    }

    public function getTask(): ?string
    {
        return $this->task;
    }

    public function setTask(string $task): self
    {
        $this->task = $task;

        return $this;
    }

    public function getSurface(): ?int
    {
        return $this->surface;
    }

    public function setSurface(int $surface): self
    {
        $this->surface = $surface;

        return $this;
    }

    public function getContent(): ?array
    {
        return $this->content;
    }

    public function setContent(array $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getAccessibility(): ?string
    {
        return $this->accessibility;
    }

    public function setAccessibility(?string $accessibility): self
    {
        $this->accessibility = $accessibility;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
