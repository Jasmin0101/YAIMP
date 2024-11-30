<?php
// объявление пространства имен
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM; // аннотации для описания сущностей 
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)] // этот класс является доктриновской сущностью, а UserRepasitoriy будет использоваться как репозиторий 
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])] // добавляет уникальное ограничение на уровне базы данных на поле username 
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')] // валидатор проверяет пользователя до того как он поподет в базу данных, ну то есть на его сущ в бд. Если проверка провалена то возвращается сообщение 
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // поля сущности 
    #[ORM\Id] // указывает на то что id является первичным ключем

    // Это аннотация Doctrine для автоматической генерации значения этого поля.
    //  В большинстве баз данных это означает, что поле будет заполняться с использованием автоинкремента.
    // Например, в MySQL поле будет автоматически увеличиваться на 1 при добавлении новой записи.
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    // PHPDoc аннотация, которая используется для документирования своиства.
    // Она помогает разрабу понять какой  тип данных ожидается от св-ва.

    // var - указывает что аннотация для описания типа данных будет переменной 

    // Collection<int, Portfolio> - означает что  св-во $partfolios будет из себя представлять коллекцию 
    // числа и элементов сущности  партфолио.

    /**
     * @var Collection<int, Portfolio>
     */

    // Описание связи один ко многим между сущностью User и Portfolio.
    // Это значит, что один пользователь может иметь несколько портфелей.
    // Collection используется для 
    // хранения нескольких объектов Portfolio, связанных с пользователем.
    // Связь настраивается через поле user в сущности Portfolio

    #[ORM\OneToMany(targetEntity: Portfolio::class, mappedBy: 'user')]
    private Collection $portfolios;

    // после создания пользователя данный метод создает пустой портфель для него (создает пустую коллекцию портфелей)
    public function __construct()
    {
        $this->portfolios = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    //задает новое имя пользователя.
    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */

    //Используется Symfony для идентификации пользователя в системе (например, при аутентификации).


    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */

    // Метод Symfony для очистки временных данных (например, паролей в открытом виде).
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Portfolio>
     */

    // получение коллекции портфелей 
    public function getPortfolios(): Collection
    {
        return $this->portfolios;
    }

    // добавление портфеля.Проверяет, есть ли уже портфель в коллекции. 
    // Если нет, добавляет его и связывает с текущим пользователем.

    public function addPortfolio(Portfolio $portfolio): static
    {
        if (!$this->portfolios->contains($portfolio)) {
            $this->portfolios->add($portfolio);
            $portfolio->setUserId($this);
        }

        return $this;
    }

    // public function removePortfolio(Portfolio $portfolio): static
    // {
    //     if ($this->portfolios->removeElement($portfolio)) {
    //         // set the owning side to null (unless already changed)
    //         if ($portfolio->getUserId() === $this) {
    //             $portfolio->setUserId(null);
    //         }
    //     }

    //     return $this;
    // }
}
