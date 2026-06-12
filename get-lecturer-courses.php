<?php

require_once 'data/database.php';

if (!isset($_GET['lecturer_id'])) {
    echo json_encode([]);
    exit;
}

$lecturerId = (int)$_GET['lecturer_id'];

$sql = "
    SELECT
        courses.id,
        courses.course
    FROM lecturer_courses
    INNER JOIN courses
        ON lecturer_courses.course_id = courses.id
    WHERE lecturer_courses.lecturer_id = ?
    ORDER BY courses.course
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$lecturerId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));