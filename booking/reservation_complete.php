<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

try {
    $db = DatabaseConnection::getInstance();

    // 예약 ID 확인
    $reservationId = $_GET['id'] ?? '';
    if (empty($reservationId)) {
        throw new Exception('예약 정보가 없습니다.');
    }

    $reservationsql = "SELECT r.*,
       TO_CHAR(s.start_time, 'YYYY-MM-DD HH24:MI:SS') as start_time,
       TO_CHAR(s.end_time, 'YYYY-MM-DD HH24:MI:SS') as end_time,
       m.title, m.running_time, t.theater_name, m.age_rating
FROM RESERVATIONS r
JOIN SCHEDULES s on r.schedule_id = s.schedule_id
JOIN MOVIES m on s.movie_id = m.movie_id
JOIN THEATERS t on s.theater_id = t.theater_id
     WHERE reservation_id = :reservation_id";

    $reservationInfo = $db->executeQuery(
        $reservationsql,
        ['reservation_id' => $reservationId]
    );


    if (empty($reservationInfo)) {
        throw new Exception('유효하지 않은 예약입니다.');
    }

    // 예약된 좌석 정보 조회
    $reservedSeats = $db->executeQuery(
        "SELECT 
            ts.seat_row,
            ts.seat_number
         FROM RESERVATION_SEATS rs
         JOIN THEATER_SEATS ts ON rs.seat_id = ts.seat_id
         WHERE rs.reservation_id = :reservation_id
         ORDER BY ts.seat_row, ts.seat_number",
        ['reservation_id' => $reservationId]
    );

    $reservationInfo = $reservationInfo[0];

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo "구체적인 오류 메시지: " . $e->getMessage(); // 임시로 추가
    $error_message = "시스템 오류가 발생했습니다. 잠시 후 다시 시도해주세요.";
}
?>


<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>예약 완료 - 무비핑</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<div class="container mx-auto p-4">
    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php else: ?>
        <!-- 예약 완료 메시지 -->
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            <strong class="font-bold">예약이 완료되었습니다!</strong>
        </div>

        <!-- 예약 정보 카드 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-4">
            <h2 class="text-2xl font-bold mb-4">예약 정보</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600">예약 번호</p>
                    <p class="font-bold"><?= htmlspecialchars($reservationInfo['RESERVATION_ID']) ?></p>
                </div>
                <div>
                    <p class="text-gray-600">예약 일시</p>
                    <p class="font-bold"><?php
                    $dateTime = DateTime::createFromFormat('d-M-y h.i.s.u A', $reservationInfo['RESERVATION_DATE']);
                    echo $dateTime->format('Y년 m월 d일 H:i');?></p>
                </div>
            </div>
        </div>

        <!-- 영화 정보 카드 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-4">
            <h2 class="text-2xl font-bold mb-4">영화 정보</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600">영화</p>
                    <p class="font-bold"><?= htmlspecialchars($reservationInfo['TITLE']) ?></p>
                </div>
                <div>
                    <p class="text-gray-600">상영관</p>
                    <p class="font-bold"><?= htmlspecialchars($reservationInfo['THEATER_NAME']) ?></p>
                </div>
                <div>
                    <p class="text-gray-600">상영 시간</p>
                    <p class="font-bold">
                    <?= date('Y년 m월 d일 H:i', strtotime($reservationInfo['START_TIME'])) ?> ~
                        <?= date('H:i', strtotime($reservationInfo['END_TIME'])) ?>
                    </p>

                </div>
                <div>
                    <p class="text-gray-600">러닝타임 / 관람등급</p>
                    <p class="font-bold"><?= $reservationInfo['RUNNING_TIME'] ?>분 / <?= $reservationInfo['AGE_RATING'] ?></p>
                </div>
            </div>
        </div>

        <!-- 좌석 정보 카드 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-4">
            <h2 class="text-2xl font-bold mb-4">좌석 정보</h2>

            <div class="flex flex-wrap gap-3 justify-center">
                <?php foreach ($reservedSeats as $seat): ?>
                    <span class="inline-block bg-red-50 border-2 border-red-200 rounded-lg px-6 py-3 text-lg font-bold text-red-600 shadow-sm hover:shadow-md transition-shadow">
               <?= htmlspecialchars($seat['SEAT_ROW']) ?><?= htmlspecialchars($seat['SEAT_NUMBER']) ?>
           </span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 버튼 -->
        <div class="flex justify-center space-x-4">
            <a href="../index.php" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                홈으로
            </a>
            <a href="../mypage" class="bg-red-500 text-white px-6 py-2 rounded hover:bg-red-600">
                예매 내역 보기
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
    // 예매 완료 페이지에 도달하면 브라우저 뒤로가기 방지
    history.pushState(null, null, location.href);
    window.onpopstate = function(event) {
        history.go(1);
    };
</script>
</body>
</html>