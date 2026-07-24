<?php
    enum Platform { case Windows; case Linux; case Apple; case Browser; case Android; }

    class Game
    {
        public function __construct(
            private ?int $id,
            private int $userId,
            private string $title,
            private string $description,
            private float $price,
            private int $discount,
            private ?string $imageBase64,
            private string $developer,
            private ?string $releaseDate,
            private string $fallbackArt = 'art-1',
            private string $visibility = 'Draft',
            private int $views = 0,
            private int $downloads = 0,
            private array $platforms = [],
            private array $categories = [],
            private array $reviews = [],
            private array $screenshots = [],
            private array $builds = []
        ) {}

        public static function fromDatabaseRow(
            array $row, 
            array $platforms = [], 
            array $categories = [], 
            array $reviews = [],
            array $screenshots = [],
            array $builds = []
        ): self {
            return new self(
                id: isset($row['id']) ? (int)$row['id'] : null,
                userId: (int)($row['user_id'] ?? 0),
                title: $row['title'] ?? 'Unknown Title',
                description: $row['description'] ?? '',
                price: (float)($row['price'] ?? 0.0),
                discount: (int)($row['discount'] ?? 0),
                imageBase64: $row['image_path'] ?? null,
                developer: $row['developer'] ?? 'Unknown Developer',
                releaseDate: $row['release_date'] ?? null,
                fallbackArt: $row['fallback_art'] ?? 'art-1',
                visibility: $row['visibility'] ?? 'Draft',
                views: (int)($row['views'] ?? 0),
                downloads: (int)($row['downloads'] ?? 0),
                platforms: $platforms,
                categories: $categories, 
                reviews: $reviews,
                screenshots: $screenshots,
                builds: $builds
            );
        }

        public function getId(): ?int { return $this->id; }
        public function getUserId(): int { return $this->userId; }
        public function getTitle(): string { return $this->title; }
        public function getDescription(): string { return $this->description; }
        public function getPrice(): float { return round($this->price, 2); }
        public function getDiscount(): int { return $this->discount; }
        public function getDiscountedPrice(): float { return round($this->price * (100 - $this->discount) / 100, 2); }
        public function getImage(): ?string { return $this->imageBase64; }
        public function getDeveloper(): string { return $this->developer; }
        public function getFallbackArt(): string { return $this->fallbackArt; }
        public function getVisibility(): string { return $this->visibility; }
        public function getViews(): int { return $this->views; }
        public function getDownloads(): int { return $this->downloads; }
        public function getPlatforms(): array { return $this->platforms; }
        public function getCategories(): array { return $this->categories; }
        public function getReviews(): array { return $this->reviews; }
        public function getScreenshots(): array { return $this->screenshots; }
        public function getBuilds(): array { return $this->builds; }

        public function getFormattedReleaseDate(): string {
            if (!$this->releaseDate) return 'TBA';
            return (new DateTime($this->releaseDate))->format('d M Y');
        }

        public function getReviewStatus(): int {
            if (empty($this->reviews)) return 1; 
            $total = count($this->reviews);
            $positive = 0;
            foreach ($this->reviews as $review) {
                if ($review->isEnjoy()) $positive++;
            }
            $pct = $positive / $total;
            if ($pct >= 0.70) return 2;
            if ($pct < 0.40) return 0;
            return 1;
        }
    }
?>