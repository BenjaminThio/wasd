<?php
    require_once __DIR__ . '/User.php';

    class Review
    {
        public function __construct(
            private User $user,
            private bool $enjoy,
            private string $description,
            private string $createdAt,
            // Needed so a review can be pointed at for editing or deletion.
            private ?int $id = null
        ) {}

        public function getId(): ?int { return $this->id; }
        public function getUser(): User { return $this->user; }
        public function isEnjoy(): bool { return $this->enjoy; }
        public function getDescription(): string { return $this->description; }

        /** Only the author may change or remove their own review. */
        public function isBy(?int $userId): bool
        {
            return $userId !== null && $this->user->getId() === $userId;
        }

        // Helper method to nicely format the date
        public function getFormattedDate(): string
        {
            $date = new DateTime($this->createdAt);
            return $date->format('F j, Y, g:i a'); // e.g., "July 18, 2026, 2:54 am"
        }

        /* --------------------------------------------------------- verdicts */

        /*
           The thresholds that turn a tally of thumbs into a verdict live here,
           once. Game reads them for an already-loaded set of reviews, and the
           reviews endpoint reads them again after a write to send the page a
           fresh figure - they must not be able to disagree.
        */

        /** 2 = mostly positive, 1 = mixed / no data, 0 = mostly negative. */
        public static function statusFor(int $total, int $positive): int
        {
            if ($total === 0) return 1;

            $percentage = $positive / $total;

            if ($percentage >= 0.70) return 2;
            if ($percentage < 0.40) return 0;
            return 1;
        }

        public static function labelFor(int $total, int $positive): string
        {
            if ($total === 0) return 'No ratings yet';

            return match (self::statusFor($total, $positive)) {
                2 => 'Overwhelmingly Positive',
                0 => 'Mostly Negative',
                default => 'Mixed',
            };
        }
    }
?>
