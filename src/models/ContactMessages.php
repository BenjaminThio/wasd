<?php
    require_once __DIR__ . '/../lib/Database.php';

    /**
     * ContactMessages
     *
     * Enquiries sent from the Contact page form.
     *
     * The validation rules live here rather than in the endpoint so that the
     * server and the browser cannot drift apart on what counts as a valid
     * message - the JavaScript on the page checks the same limits to give
     * instant feedback, but this is the copy that decides.
     */
    class ContactMessages
    {
        public const TOPICS = ['General', 'Support', 'Partnership', 'Press'];

        public const NAME_MIN = 2;
        public const NAME_MAX = 100;
        public const MESSAGE_MIN = 10;
        public const MESSAGE_MAX = 2000;

        /**
         * Checks a submitted enquiry and returns the first thing wrong with it,
         * or null when it is fine.
         */
        public static function validate(string $name, string $email, string $topic, string $message): ?string
        {
            if ($name === '' || $email === '' || $message === '') {
                return 'Fill in your name, email and message before sending.';
            }

            $length = mb_strlen($name);
            if ($length < self::NAME_MIN || $length > self::NAME_MAX) {
                return 'Your name should be between ' . self::NAME_MIN . ' and ' . self::NAME_MAX . ' characters.';
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 191) {
                return 'Enter a valid email address so we can reply to you.';
            }

            if (!in_array($topic, self::TOPICS, true)) {
                return 'Choose one of the listed topics.';
            }

            $length = mb_strlen($message);
            if ($length < self::MESSAGE_MIN) {
                return 'Tell us a little more - at least ' . self::MESSAGE_MIN . ' characters.';
            }

            if ($length > self::MESSAGE_MAX) {
                return 'Messages are limited to ' . self::MESSAGE_MAX . ' characters.';
            }

            return null;
        }

        /** Stores an enquiry and returns its id. */
        public static function create(
            string $name,
            string $email,
            string $topic,
            string $message,
            ?int $userId = null,
            ?Database $database = null
        ): int {
            $database ??= new Database();

            $database->insert('contact_message', [
                'user_id' => $userId,
                'name'    => $name,
                'email'   => $email,
                'topic'   => $topic,
                'message' => $message,
            ]);

            return $database->lastInsertId();
        }

        /* ------------------------------------------------------- reading them */

        /**
         * Every enquiry, newest first, for the staff inbox.
         *
         * LEFT JOIN rather than INNER: most enquiries have no account behind
         * them, and an INNER JOIN would silently hide exactly those.
         */
        public static function all(int $limit = 100, ?Database $database = null): array
        {
            $database ??= new Database();

            return $database->query(
                'SELECT m.*, u.username
                   FROM contact_message m
                   LEFT JOIN user u ON u.id = m.user_id
                  ORDER BY m.created_at DESC, m.id DESC
                  LIMIT ?',
                [$limit]
            )->fetchAll();
        }

        /** How many are still unread, for the badge on the inbox link. */
        public static function unreadCount(?Database $database = null): int
        {
            $database ??= new Database();

            $row = $database->query(
                'SELECT COUNT(*) AS total FROM contact_message WHERE status = ?', ['New']
            )->fetch();

            return (int)($row['total'] ?? 0);
        }

        /** Flips one enquiry between New and Read. */
        public static function setStatus(int $id, string $status, ?Database $database = null): bool
        {
            if (!in_array($status, ['New', 'Read'], true)) {
                return false;
            }

            $database ??= new Database();

            return $database->query(
                'UPDATE contact_message SET status = ? WHERE id = ?', [$status, $id]
            )->rowCount() > 0;
        }

        /**
         * Marks every unread enquiry as read, and reports how many changed.
         *
         * The WHERE clause is not decoration. Without it the statement would
         * touch every row in the table to set most of them to the value they
         * already hold, and the count it returns would be meaningless.
         */
        public static function markAllRead(?Database $database = null): int
        {
            $database ??= new Database();

            return $database->query(
                'UPDATE contact_message SET status = ? WHERE status = ?', ['Read', 'New']
            )->rowCount();
        }

        public static function delete(int $id, ?Database $database = null): bool
        {
            $database ??= new Database();

            return $database->query(
                'DELETE FROM contact_message WHERE id = ?', [$id]
            )->rowCount() > 0;
        }

        /**
         * How many enquiries an address has sent in the last hour.
         *
         * The form is open to guests, so nothing but this stands between it and
         * someone holding down the send button. Counting by address rather than
         * by session means clearing cookies does not reset the allowance.
         */
        public static function recentCountFor(string $email, ?Database $database = null): int
        {
            $database ??= new Database();

            $row = $database->query(
                'SELECT COUNT(*) AS total
                   FROM contact_message
                  WHERE email = ?
                    AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)',
                [$email]
            )->fetch();

            return (int)($row['total'] ?? 0);
        }
    }
?>
