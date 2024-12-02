<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // 에러를 JSON 응답에 포함시키지 않기 위해 0으로 설정

require_once '../includes/db_connect.php';
session_start();

// JSON 헤더 설정
header('Content-Type: application/json');

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => '로그인이 필요합니다.'
    ]);
    exit();
}

try {
    $db = DatabaseConnection::getInstance();

    // POST 데이터 확인
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['schedule_id']) || empty($_POST['selected_seats'])) {
        throw new Exception('잘못된 요청입니다.');
    }

    $userId = $_SESSION['user_id'];
    $scheduleId = $_POST['schedule_id'];
    $selectedSeats = explode(',', $_POST['selected_seats']);

    // 예약 ID 생성
    $reservationId = 'R' . sprintf('%09d', $db->executeQuery("SELECT reservation_seq.NEXTVAL FROM DUAL")[0]['NEXTVAL']);

    // 예약 정보 저장
    $db->executeNonQuery(
        "INSERT INTO RESERVATIONS (
            reservation_id, user_id, schedule_id, 
            status
        ) VALUES (
            :reservation_id, :user_id, :schedule_id, 
            'Confirmed'
        )",
        [
            'reservation_id' => $reservationId,
            'user_id' => $userId,
            'schedule_id' => $scheduleId
        ]
    );

    // 선택된 좌석들에 대한 예약 처리
    foreach ($selectedSeats as $seatId) {
        // 예약 좌석 정보 저장
        $db->executeNonQuery(
            "INSERT INTO RESERVATION_SEATS (
                reservation_id, seat_id
            ) VALUES (
                :reservation_id, :seat_id
            )",
            [
                'reservation_id' => $reservationId,
                'seat_id' => $seatId
            ]
        );

        // 스케줄 좌석 상태 업데이트
        $db->executeNonQuery(
            "INSERT INTO SCHEDULE_SEATS (
                schedule_id, seat_id, status
            ) VALUES (
                :schedule_id, :seat_id, 'OCCUPIED'
            )",
            [
                'schedule_id' => $scheduleId,
                'seat_id' => $seatId
            ]
        );
    }

    echo json_encode([
        'success' => true,
        'message' => '예약이 완료되었습니다.',
        'reservation_id' => $reservationId
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>