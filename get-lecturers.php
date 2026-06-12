<?php
require_once 'data/database.php';

header('Content-Type: application/json; charset=utf-8');

$sql = "
    SELECT
        l.id,
        l.name,
        l.department_id,
        d.department,
        COALESCE(c.course_list, '') AS courses,
        COALESCE(r.average_rating, 0) AS average_rating
    FROM lecturers l
    INNER JOIN departments d
        ON l.department_id = d.id
    LEFT JOIN (
        SELECT
            lc.lecturer_id,
            GROUP_CONCAT(DISTINCT c.course ORDER BY c.course SEPARATOR ', ') AS course_list
        FROM lecturer_courses lc
        INNER JOIN courses c
            ON lc.course_id = c.id
        GROUP BY lc.lecturer_id
    ) c
        ON c.lecturer_id = l.id
    LEFT JOIN (
        SELECT
            lecturer_id,
            ROUND(AVG(CAST(rating AS DECIMAL(10,2))), 1) AS average_rating
        FROM reviews
        GROUP BY lecturer_id
    ) r
        ON r.lecturer_id = l.id
    ORDER BY l.name ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$lecturers = $stmt->fetchAll();

echo json_encode($lecturers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>