<?php
    class Page
    {
        private string $title;
        private string $content;
        private string $cssUrl;

        public function __construct()
        {
            $this->title = 'WASD';
            $this->content = '';
            $this->cssUrl = '';
        }

        public function getTitle(): string
        {
            return $this->title;
        }

        public function getContent(): string
        {
            return $this->content;
        }

        public function getCssUrl(): string
        {
            return $this->cssUrl;
        }

        public function setTitle(string $title): void
        {
            $this->title = 'WASD | ' . ucwords(strtolower($title));
        }

        public function setContent(string $content): void
        {
            $this->content = $content;
        }

        public function setCssUrl(string $cssUrl): void
        {
            $this->cssUrl = $cssUrl;
        }
    }
?>