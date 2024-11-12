<?php
require_once 'db_connect.php';

try {
    $db = DatabaseConnection::getInstance();

    // 1. 특정 상영일정 전체 조회
    $schedules = $db->executeQuery(
        "SELECT s.schedule_id, 
                s.start_time,
                s.end_time,
                t.theater_name,
                m.title as movie_title,
                p.category_name,
                p.base_price,
                (SELECT COUNT(*) 
                 FROM SCHEDULE_SEATS ss 
                 WHERE ss.schedule_id = s.schedule_id 
                 AND ss.status = 'AVAILABLE') as available_seats
         FROM SCHEDULES s
         JOIN THEATERS t ON s.theater_id = t.theater_id
         JOIN MOVIES m ON s.movie_id = m.movie_id
         JOIN PRICE_CATEGORIES p ON s.price_category_id = p.category_id
         ORDER BY s.start_time"
    );

    echo "<pre>";
    echo "=== 전체 상영일정 ===\n";
    print_r($schedules);
    echo "</pre>";

    // 2. 특정 극장, 영화, 날짜의 상영일정 조회
    $testTheaterId = 'T000000001'; // 실제 존재하는 극장 ID로 변경
    $testMovieId = 'M000000001';   // 실제 존재하는 영화 ID로 변경
    $testDate = date('Y-m-d'); // 오늘 날짜

    $specificSchedules = $db->executeQuery(
        "SELECT s.schedule_id, 
                TO_CHAR(s.start_time, 'YYYY-MM-DD HH24:MI') as start_time,
                TO_CHAR(s.end_time, 'YYYY-MM-DD HH24:MI') as end_time,
                t.theater_name,
                m.title as movie_title,
                p.category_name,
                p.base_price,
                (SELECT COUNT(*) 
                 FROM SCHEDULE_SEATS ss 
                 WHERE ss.schedule_id = s.schedule_id 
                 AND ss.status = 'AVAILABLE') as available_seats
         FROM SCHEDULES s
         JOIN THEATERS t ON s.theater_id = t.theater_id
         JOIN MOVIES m ON s.movie_id = m.movie_id
         JOIN PRICE_CATEGORIES p ON s.price_category_id = p.category_id
         WHERE s.theater_id = :theater_id 
         AND s.movie_id = :movie_id 
         AND TRUNC(s.start_time) = TRUNC(TO_DATE(:show_date, 'YYYY-MM-DD'))
         ORDER BY s.start_time",
        [
            'theater_id' => $testTheaterId,
            'movie_id' => $testMovieId,
            'show_date' => $testDate
        ]
    );

    echo "<pre>";
    echo "=== 특정 상영일정 ===\n";
    echo "극장ID: $testTheaterId\n";
    echo "영화ID: $testMovieId\n";
    echo "날짜: $testDate\n";
    print_r($specificSchedules);
    echo "</pre>";

} catch (Exception $e) {
    echo "에러 발생: " . $e->getMessage();
}