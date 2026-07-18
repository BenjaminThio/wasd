<?php
    require_once __DIR__ . '/User.php';

    class Review
    {
        public function __construct(
            private User $user,
            private bool $enjoy,
            private string $description,
            private string $createdAt
        ) {}

        public function getUser(): User { return $this->user; }
        public function isEnjoy(): bool { return $this->enjoy; }
        public function getDescription(): string { return $this->description; }
        
        // Helper method to nicely format the date
        public function getFormattedDate(): string 
        { 
            $date = new DateTime($this->createdAt);
            return $date->format('F j, Y, g:i a'); // e.g., "July 18, 2026, 2:54 am"
        }
    }
?>