<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('로그인이 필요한 서비스입니다.'); window.location.href='../auth/';</script>";
    exit;
}

// 예약 ID 확인
if (!isset($_GET['reservation_id'])) {
    echo "<script>alert('예약 정보가 필요합니다.'); window.location.href='../mypage/';</script>";
    exit;
}

try {
    $db = DatabaseConnection::getInstance();

    // 첫 번째 쿼리: 예약 존재 여부 확인
    $check_reservation = "
       SELECT * 
       FROM RESERVATIONS 
       WHERE reservation_id = :reservation_id";

    $reservation_check = $db->executeQuery($check_reservation, [
        'reservation_id' => $_GET['reservation_id']
    ]);

    if (empty($reservation_check)) {
        throw new Exception('예약 정보를 찾을 수 없습니다.');
    }

    // 두 번째 쿼리: 사용자 권한 확인
    $check_user_id = "
       SELECT * 
       FROM RESERVATIONS 
       WHERE user_id = :user_id";

    $user_id_check = $db->executeQuery($check_user_id, [
        'user_id' => $_SESSION['user_id']
    ]);

    if (empty($user_id_check)) {
        throw new Exception('해당 예약에 대한 권한이 없습니다.');
    }

    // 세 번째 쿼리: 예약 상태 확인
    $check_status = "
       SELECT * 
       FROM RESERVATIONS 
       WHERE reservation_id = :reservation_id 
       AND status = 'Confirmed'";

    $status_check = $db->executeQuery($check_status, [
        'reservation_id' => $_GET['reservation_id']
    ]);

    if (empty($status_check)) {
        throw new Exception('이미 취소된 예약이거나 처리할 수 없는 예약입니다.');
    }

    // 스케줄 ID 가져오기
    $get_schedule = "
       SELECT schedule_id 
       FROM RESERVATIONS 
       WHERE reservation_id = :reservation_id";

    $schedule_result = $db->executeQuery($get_schedule, [
        'reservation_id' => $_GET['reservation_id']
    ]);

    // 예약된 좌석 ID 가져오기
    $get_seats = "
       SELECT seat_id 
       FROM RESERVATION_SEATS 
       WHERE reservation_id = :reservation_id";

    $seats_result = $db->executeQuery($get_seats, [
        'reservation_id' => $_GET['reservation_id']
    ]);

    // SCHEDULE_SEATS 상태 업데이트 - schedule_id로 먼저 찾기
    foreach ($seats_result as $seat) {
        $update_schedule = "
           DELETE FROM SCHEDULE_SEATS 
           WHERE schedule_id = :schedule_id";

        $db->executeNonQuery($update_schedule, [
            'schedule_id' => $schedule_result[0]['SCHEDULE_ID']
        ]);

        // 그 다음 seat_id로 업데이트
        $update_seat = "
           DELETE FROM SCHEDULE_SEATS 
           WHERE seat_id = :seat_id";

        $db->executeNonQuery($update_seat, [
            'seat_id' => $seat['SEAT_ID']
        ]);
    }

    // 예약된 좌석 삭제
    $delete_seats = "
       DELETE FROM RESERVATION_SEATS 
       WHERE reservation_id = :reservation_id";

    $db->executeNonQuery($delete_seats, [
        'reservation_id' => $_GET['reservation_id']
    ]);

    // 예약 삭제
    $delete_reservation = "
       DELETE FROM RESERVATIONS 
       WHERE reservation_id = :reservation_id";

    $db->executeNonQuery($delete_reservation, [
        'reservation_id' => $_GET['reservation_id']
    ]);

    echo "<script>
       alert('예약이 성공적으로 취소되었습니다.');
       window.location.href='../mypage/';
   </script>";

} catch (Exception $e) {
    echo "<script>
       alert('예약 취소 중 오류가 발생했습니다: " . htmlentities($e->getMessage(), ENT_QUOTES) . "');
       window.location.href='../mypage/';
   </script>";
}
?>