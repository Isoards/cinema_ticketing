<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db_connect.php';
session_start();

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
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

    // 스케줄 정보 조회
    $scheduleInfo = $db->executeQuery(
        "SELECT s.schedule_id, p.base_price
         FROM SCHEDULES s
         JOIN PRICE_CATEGORIES p ON s.price_category_id = p.category_id
         WHERE s.schedule_id = :schedule_id",
        ['schedule_id' => $scheduleId]
    );

    if (empty($scheduleInfo)) {
        throw new Exception('유효하지 않은 스케줄입니다.');
    }

    $basePrice = $scheduleInfo[0]['BASE_PRICE'];
    $totalPrice = $basePrice * count($selectedSeats);

    // 예약 ID 생성
    $reservationId = 'R' . sprintf('%09d', $db->executeQuery("SELECT reservation_seq.NEXTVAL FROM DUAL")[0]['NEXTVAL']);

    try {
        // 예약 정보 저장
        $success = $db->executeNonQuery(
            "INSERT INTO RESERVATIONS (
                reservation_id, user_id, schedule_id, total_price, 
                status, payment_method, payment_status
            ) VALUES (
                :reservation_id, :user_id, :schedule_id, :total_price,
                'Confirmed', 'CARD', 'COMPLETED'
            )",
            [
                'reservation_id' => $reservationId,
                'user_id' => $userId,
                'schedule_id' => $scheduleId,
                'total_price' => $totalPrice
            ]
        );

        // 선택된 좌석들에 대한 예약 처리
        foreach ($selectedSeats as $seatId) {
            // 예약 좌석 정보 저장
            $db->executeNonQuery(
                "INSERT INTO RESERVATION_SEATS (
                    reservation_id, seat_id, price
                ) VALUES (
                    :reservation_id, :seat_id, :price
                )",
                [
                    'reservation_id' => $reservationId,
                    'seat_id' => $seatId,
                    'price' => $basePrice
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

        // 성공 응답
        echo json_encode([
            'success' => true,
            'message' => '예약이 완료되었습니다.',
            'reservation_id' => $reservationId
        ]);

    } catch (Exception $e) {
        throw new Exception('예약 처리 중 오류가 발생했습니다: ' . $e->getMessage());
    }

} catch (Exception $e) {
    error_log("Reservation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>