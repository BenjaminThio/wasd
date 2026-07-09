<?php
    enum Platform
    {
        case Windows;
        case Linux;
        case MacOS;
        case Browser;
        case Android;
        case IOS;
    }

    class Game
    {
        private string $title;
        private string $description;
        private string $imageBase64;
        private float $price;
        private array $platforms = [];

        public function __construct(string $title, string $description, string $imageBase64, float $price, array $platforms)
        {
            $this->title = $title;
            $this->description = $description;
            $this->price = $price;
            $this->imageBase64 = $imageBase64;
            $this->platforms = $platforms;
        }

        public function getTitle()
        {
            return $this->title;
        }

        public function getDescription()
        {
            return $this->description;
        }

        public function getPlatforms()
        {
            return $this->platforms;
        }
    }
?>