<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
class Article
{
    public const STATUS_DRAFT = 'brouillon';
    public const STATUS_PUBLISHED = 'publie';
    public const STATUS_ARCHIVED = 'archive';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;


    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $date_creation = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $date_publication = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $auteur = null;

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: Comment::class, orphanRemoval: true)]
    private Collection $comments;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: ArticleLike::class)]
    private Collection $likes;

    #[ORM\OneToMany(mappedBy: 'article', targetEntity: ArticleView::class)]
    private Collection $views;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->views = new ArrayCollection();
        $this->date_creation = new \DateTimeImmutable();
        $this->statut = 'brouillon';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }



    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeImmutable $date_creation): static
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    public function getDatePublication(): ?\DateTimeImmutable
    {
        return $this->date_publication;
    }

    public function setDatePublication(?\DateTimeImmutable $date_publication): static
    {
        $this->date_publication = $date_publication;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getAuteur(): ?User
    {
        return $this->auteur;
    }

    public function setAuteur(?User $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setArticle($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getArticle() === $this) {
                $comment->setArticle(null);
            }
        }

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection<int, ArticleLike>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(ArticleLike $like): static
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setArticle($this);
        }

        return $this;
    }

    public function removeLike(ArticleLike $like): static
    {
        if ($this->likes->removeElement($like)) {
            // set the owning side to null (unless already changed)
            if ($like->getArticle() === $this) {
                $like->setArticle(null);
            }
        }

        return $this;
    }

    public function getLikeCount(): int
    {
        return $this->likes->count();
    }

    public function isPublished(): bool
    {
        return $this->statut === 'publie' && $this->date_publication !== null;
    }

    public function isArchived(): bool
    {
        return $this->statut === 'archive';
    }

    public function isDraft(): bool
    {
        return $this->statut === 'brouillon';
    }

    public function __toString(): string
    {
        return $this->titre;
    }

    /**
     * @return Collection<int, ArticleView>
     */
    public function getViews(): Collection
    {
        return $this->views;
    }

    public function getUniqueViewsCount(): int
    {
        return $this->views->count();
    }

    public function addView(ArticleView $view): static
    {
        if (!$this->views->contains($view)) {
            $this->views->add($view);
            $view->setArticle($this);
        }

        return $this;
    }

    public function removeView(ArticleView $view): static
    {
        if ($this->views->removeElement($view)) {
            if ($view->getArticle() === $this) {
                $view->setArticle(null);
            }
        }

        return $this;
    }
}