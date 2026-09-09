<?php

namespace app\Models;

class User {
    private ?int $id;
    private ?int $roleId;
    private ?string $googleId;
    private string $email;
    private string $name;
    private ?string $pictureUrl;
    private ?string $createdAt;

    public function __construct(
        string $email,
        string $name,
        ?int $roleId = null,
        ?string $googleId = null,
        ?string $pictureUrl = null,
        ?int $id = null,
        ?string $createdAt = null
    ) {
        $this->id = $id;
        $this->roleId = $roleId;
        $this->googleId = $googleId;
        $this->email = $email;
        $this->name = $name;
        $this->pictureUrl = $pictureUrl;
        $this->createdAt = $createdAt;
    }

    // --- GETTERS & SETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getRoleId(): ?int
    {
        return $this->roleId;
    }

    public function setRoleId(?int $roleId): self
    {
        $this->roleId = $roleId;
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getPictureUrl(): ?string
    {
        return $this->pictureUrl;
    }

    public function setPictureUrl(?string $pictureUrl): self
    {
        $this->pictureUrl = $pictureUrl;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
   }
}