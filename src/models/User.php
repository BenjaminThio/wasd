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
            private array $wishlist = [],
            private bool $isAdmin = false
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
                wishlist: $wishlist,
                isAdmin: (bool)($row['is_admin'] ?? false)
            );
        }

        // Getters
        public function getId(): ?int { return $this->id; }
        public function getUsername(): string { return $this->username; }
        public function getEmail(): string { return $this->email; }
        public function getPassword(): string { return $this->password; }
        public function getAvatarPath(): ?string { return $this->avatarPath; }

        /** Staff. Only these accounts may read the contact inbox. */
        public function isAdmin(): bool { return $this->isAdmin; }
        
        /**
         * Only populated when a caller passes them in explicitly. Users::getById()
         * deliberately leaves them empty - loading every game in a cart on every
         * request cost more than the rest of the page put together.
         *
         * Read the cart and wishlist through src/app/api/cart and
         * src/app/api/wishlist, which page and filter them.
         */
        public function getCart(): array { return $this->cart; }
        public function getWishlist(): array { return $this->wishlist; }
    }
?>