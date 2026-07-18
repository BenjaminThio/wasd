<?php
    class User
    {
        public function __construct(
            private ?int $id,
            private string $username,
            private string $email,
            private string $password,
            private ?string $avatarPath,
            private array $cart = [],
            private array $wishlist = []
        ) {}

        // Factory method to easily create a User from a database row
        public static function fromDatabaseRow(array $row, array $cart = [], array $wishlist = []): self 
        {
            return new self(
                id: $row['id'] ?? null,
                username: $row['username'] ?? '',
                email: $row['email'] ?? '',
                password: $row['password'] ?? '',
                avatarPath: $row['avatar_path'] ?? null,
                cart: $cart,
                wishlist: $wishlist
            );
        }

        // Getters
        public function getId(): ?int { return $this->id; }
        public function getUsername(): string { return $this->username; }
        public function getEmail(): string { return $this->email; }
        public function getPassword(): string { return $this->password; }
        public function getAvatarPath(): ?string { return $this->avatarPath; }
        
        // High-level Getters for E-commerce logic
        public function getCart(): array { return $this->cart; }
        public function getWishlist(): array { return $this->wishlist; }
    }
?>