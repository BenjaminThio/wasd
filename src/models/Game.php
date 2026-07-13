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
        private string $title;
        private string $description;
        private float $price;
        private int $discount;
        private string $imageBase64;
        private array $platforms = [];
        private array $categories = [];

        public function __construct(
            string $title,
            string $description,
            float $price,
            int $discount,
            string $imageBase64,
            array $platforms,
            array $categories)
        {
            $this->title = $title;
            $this->description = $description;
            $this->price = $price;
            $this->discount = $discount;
            $this->imageBase64 = $imageBase64;
            $this->platforms = $platforms;
            $this->categories = $categories;
        }

        public function getTitle(): string
        {
            return $this->title;
        }

        public function getDescription(): string
        {
            return $this->description;
        }

        public function getPrice(): float
        {
            return round($this->price, 2);
        }

        public function getDiscount(): int
        {
            return $this->discount;
        }

        public function getDiscountedPrice(): float
        {
            return round($this->price * (100 - $this->discount) / 100, 2);
        }

        public function getPlatforms(): array
        {
            return $this->platforms;
        }

        public function getCategories(): array
        {
            return $this->categories;
        }
    }
?>