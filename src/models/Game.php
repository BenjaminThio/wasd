<?php
    enum Platform
    {
        case Windows;
        case Linux;
        case Apple;
        case Browser;
        case Android;
    }

    class Game
    {
        public function __construct(
            private ?int $id,
            private string $title,
            private string $description,
            private float $price,
            private int $discount,
            private string $imageBase64,
            private array $platforms = [],
            private array $categories = [],
            private array $reviews = []
        ) {}

        public static function fromDatabaseRow(array $row, array $platforms = [], array $categories = [], array $reviews = []): self 
        {
            return new self(
                id: $row['id'] ?? null,
                title: $row['title'] ?? 'Unknown Title',
                description: $row['description'] ?? '',
                price: (float)($row['price'] ?? 0.0),
                discount: (int)($row['discount'] ?? 0),
                imageBase64: $row['image_path'] ?? '',
                platforms: $platforms,
                categories: $categories, 
                reviews: $reviews
            );
        }

        public function getId(): ?int { return $this->id; }
        public function getTitle(): string { return $this->title; }
        public function getDescription(): string { return $this->description; }
        public function getPrice(): float { return round($this->price, 2); }
        public function getDiscount(): int { return $this->discount; }
        public function getDiscountedPrice(): float { 
            return round($this->price * (100 - $this->discount) / 100, 2); 
        }
        public function getImage(): string { return $this->imageBase64; }
        public function getPlatforms(): array { return $this->platforms; }
        public function getCategories(): array { return $this->categories; }
        public function getReviews(): array { return $this->reviews; }
        public function getReviewStatus(): int 
        {
            // If nobody has reviewed the game yet, default to Orange (Mixed/Neutral)
            if (empty($this->reviews)) {
                return 1; 
            }

            $totalReviews = count($this->reviews);
            $positiveReviews = 0;

            foreach ($this->reviews as $review) {
                if ($review->isEnjoy()) {
                    $positiveReviews++;
                }
            }

            $percentage = $positiveReviews / $totalReviews;

            if ($percentage >= 0.70) {
                return 2; // Green (>= 70% Positive)
            } elseif ($percentage < 0.40) {
                return 0; // Red (< 40% Positive)
            } else {
                return 1; // Orange (40% - 69% Mixed)
            }
        }
    }
?>