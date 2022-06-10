<?php

namespace App\Entity;

use App\Model\TimestampedInterface;
use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups('comment')]
    private ?int $id;

    #[ORM\Column(type: 'text')]
    #[Groups('comment')]
    private ?string $content;

    #[ORM\Column(type: 'datetime')]
    #[Groups('comment')]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private Article $article;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private UserInterface $user;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    private ?self $parent;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $replies;

    public function __construct(Article $article, UserInterface $user)
    {
        $this->article = $article;
        $this->user = $user;
        $this->replies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): self
    {
        $this->article = $article;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function addReply(Comment $comment): self
    {
        if (!$this->replies->contains($comment)) {
            $this->replies->add($comment);
            $comment->setParent($this);
        }

        return $this;
    }

    public function getReplies(): Collection
    {
        return $this->replies;
    }

    #[Groups('comment')]
    public function getUserId(): ?int
    {
        return $this->user?->getId();
    }

    #[Groups('comment')]
    public function getUsername(): ?string
    {
        return $this->user?->getUsername();
    }

    #[Groups('comment')]
    public function getParentId(): ?int
    {
        return $this->parent?->getId();
    }

    public function __toString(): string
    {
        return "{$this->user->getUsername()} {$this->createdAt->format('d/m/y à H:i:s')}";
    }
}
