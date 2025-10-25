<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet e-mail.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // 🔐 CHAMPS AUTHENTIFICATION (OBLIGATOIRES)
    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L\'e-mail est requis.')]
    #[Assert\Email(message: 'L\'e-mail "{{ value }}" n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le mot de passe est requis.')]
    #[Assert\Length(min: 6, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.')]
    private ?string $password = null;

    // 👤 CHAMPS IDENTITÉ (OBLIGATOIRES POUR INSCRIPTION)
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est requis.')]
    #[Assert\Length(max: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est requis.')]
    #[Assert\Length(max: 100)]
    private ?string $nom = null;

    // 📅 CHAMPS GESTION COMPTE
    #[ORM\Column]
    private ?\DateTimeImmutable $date_creation = null;

    #[ORM\Column]
    private ?bool $est_verifie = null;

    // 🔑 CHAMPS VALIDATION EMAIL
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $token_verification = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $date_verification = null;

    // 🔄 CHAMPS RÉINITIALISATION MOT DE PASSE
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reset_token = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reset_token_created_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reset_token_expires_at = null;

    // 🖼️ CHAMPS PROFIL (OPTIONNELS)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $biographie = null;

    // 🔗 RELATIONS (POUR LE BLOG)
    #[ORM\OneToMany(mappedBy: 'auteur', targetEntity: Article::class, orphanRemoval: true)]
    private Collection $articles;

    #[ORM\OneToMany(mappedBy: 'auteur', targetEntity: Comment::class, orphanRemoval: true)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: ArticleLike::class, orphanRemoval: true)]
    private Collection $likes;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->likes = new ArrayCollection();
        $this->date_creation = new \DateTimeImmutable();
        $this->est_verifie = false;
        $this->roles = ['ROLE_USER'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower($email);

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_values(array_unique($roles));
    }

    /**
     * @param array<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->date_creation;
    }

    public function isEstVerifie(): ?bool
    {
        return $this->est_verifie;
    }

    public function setEstVerifie(bool $est_verifie): self
    {
        $this->est_verifie = $est_verifie;

        return $this;
    }

    public function getTokenVerification(): ?string
    {
        return $this->token_verification;
    }

    public function setTokenVerification(?string $token): self
    {
        $this->token_verification = $token;

        return $this;
    }

    public function getDateVerification(): ?\DateTimeImmutable
    {
        return $this->date_verification;
    }

    public function setDateVerification(?\DateTimeImmutable $date): self
    {
        $this->date_verification = $date;

        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->reset_token;
    }

    public function setResetToken(?string $token): self
    {
        $this->reset_token = $token;

        return $this;
    }

    public function getResetTokenCreatedAt(): ?\DateTimeImmutable
    {
        return $this->reset_token_created_at;
    }

    public function setResetTokenCreatedAt(?\DateTimeImmutable $date): self
    {
        $this->reset_token_created_at = $date;

        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->reset_token_expires_at;
    }

    public function setResetTokenExpiresAt(?\DateTimeImmutable $date): self
    {
        $this->reset_token_expires_at = $date;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getBiographie(): ?string
    {
        return $this->biographie;
    }

    public function setBiographie(?string $biographie): self
    {
        $this->biographie = $biographie;

        return $this;
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setAuteur($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): self
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getAuteur() === $this) {
                $article->setAuteur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setAuteur($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getAuteur() === $this) {
                $comment->setAuteur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ArticleLike>
     */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(ArticleLike $like): self
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setUser($this);
        }

        return $this;
    }

    public function removeLike(ArticleLike $like): self
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getUser() === $this) {
                $like->setUser(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->email;
    }
}
