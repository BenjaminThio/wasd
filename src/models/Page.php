<?php
    class Page
    {
        private string $title;
        private string $content;
        private string $cssUrl;

        /**
         * Some pages only work as a real document, because they depend on
         * response headers the SPA swap never delivers - the game page sends
         * cross-origin isolation headers so an embedded HTML5 build can use
         * SharedArrayBuffer. The router turns a soft navigation into a full
         * one when this is set.
         */
        private bool $requiresFullLoad = false;

        public function __construct()
        {
            $this->title = 'WASD';
            $this->content = '';
            $this->cssUrl = '';
        }

        public function requiresFullLoad(): bool
        {
            return $this->requiresFullLoad;
        }

        public function setRequiresFullLoad(bool $required): void
        {
            $this->requiresFullLoad = $required;
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

        /**
         * Pages may override this while they render, e.g. the game page sets
         * the game's name. Routes that do not get a readable title derived
         * from the URL by the router.
         */
        public function setTitle(string $title): void
        {
            $title = trim($title);
            $this->title = $title === '' ? 'WASD' : 'WASD | ' . $title;
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