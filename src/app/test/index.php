<div>
    <?php
        require __DIR__ . '/../../lib/Database.php';

        $database = new Database();

        $database->query(
            'CREATE TABLE STUDENT (
                StudentId int PRIMARY KEY,
                StudentName varchar(255) NOT NULL,
                Address varchar(255)
            )'
        );
    ?>
</div>