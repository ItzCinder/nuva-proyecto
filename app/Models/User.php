<?php

namespace app\Models;

class User extends Model {
    private ?int $id;
    private ?string $googleId;
    private string $email;
    private string $name;
    private ?string $pictureUrl;
    private ?string $createdAt;

    public function __construct(
        string $email,
        string $name,
        ?string $googleId = null,
        ?string $pictureUrl = null,
        ?int $id = null,
        ?string $createdAt = null
    ) {
        parent::__construct();
        $this->id = $id;
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

    // --- DATABASE METHODS ---

    /*
    Metodo "Inteligente"
    Puede decir si hacer UPDATE o INSERT basandose en si le proporcionas el id o no.
    Si el ID proporcionado no existe en la BD directamente no hace nada xd
    */
    public function save(): bool
    {
        if ($this->id === null) {
            return $this->insert();
        }
        return $this->update();
    }

    private function insert(): bool
    {
        $sql = "INSERT INTO users (email, name, google_id, picture_url, created_at) 
                VALUES (:email, :name, :google_id, :picture_url,  :created_at)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':email'         => $this->email,
            ':name'          => $this->name,
            ':google_id'     => $this->googleId,
            ':picture_url'   => $this->pictureUrl,
            ':created_at'    => $this->createdAt ?? date('Y-m-d H:i:s'),
        ]);

        if ($result) {
            $this->id = (int) $this->db->lastInsertId();
        }

        return $result;
    }

    private function update(): bool
    {
        $sql = "UPDATE users SET email = :email, name = :name, google_id = :google_id, 
                picture_url = :picture_url WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':email'       => $this->email,
            ':name'        => $this->name,
            ':google_id'   => $this->googleId,
            ':picture_url' => $this->pictureUrl,
            ':id'          => $this->id,
        ]);
    }

    /*
    Encontrar usuario por ID
    */
    public static function findById(int $id): ?User
    {
        $db = \Database::getConnection();
        $sql = "SELECT * FROM users WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new self(
            $row['email'],
            $row['name'],
            $row['google_id'],
            $row['picture_url'],
            $row['id'],
            $row['created_at']
        );
    }

    /*
    Encontrar usuario por Email
    */
    public static function findByEmail(string $email): ?User
    {
        $db = \Database::getConnection();
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new self(
            $row['email'],
            $row['name'],
            $row['google_id'],
            $row['picture_url'],
            $row['id'],
            $row['created_at']
        );
    }

    /*
    Encontrar usuario por GoogleID
    */
    public static function findByGoogleId(string $googleId): ?User
    {
        $db = \Database::getConnection();
        $sql = "SELECT * FROM users WHERE google_id = :google_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':google_id' => $googleId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return new self(
            $row['email'],
            $row['name'],
            $row['google_id'],
            $row['picture_url'],
            $row['id'],
            $row['created_at']
        );
    }
}