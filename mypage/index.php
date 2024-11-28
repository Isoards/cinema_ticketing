<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('로그인이 필요한 서비스입니다.'); window.location.href='../auth/index.php';</script>";
    exit;
}

try {
    $db = DatabaseConnection::getInstance();
    $user_id = $_SESSION['user_id'];

    // 프로필 존재 여부 확인
    $check_profile = "SELECT 1 FROM profiles WHERE user_id = :user_id";
    $profile_exists = $db->executeQuery($check_profile, ['user_id' => $user_id]);

    // 프로필이 없으면 edit_profile.php로 리다이렉트
    if (empty($profile_exists)) {
        echo "<script>
           alert('프로필 정보를 먼저 작성해주세요.');
           window.location.href='edit_profile.php';
       </script>";
        exit;
    }

    // 프로필 정보 조회
    $profile_query = "
        SELECT p.*, u.email, u.phone_number, 
               TO_CHAR(u.created_at, 'YYYY-MM-DD') as created_at,
               g.genre_name
        FROM profiles p
        LEFT JOIN users u ON p.user_id = u.user_id
        LEFT JOIN genres g ON p.preferred_genre_id = g.genre_id
        WHERE p.user_id = :user_id
    ";

    $profile = $db->executeQuery($profile_query, ['user_id' => $user_id]);

    // 예약 내역 조회
    $reservation_query = "SELECT r.*, TO_CHAR(s.start_time, 'YYYY-MM-DD HH24:MI:SS') as start_time, s.end_time, m.title, m.running_time, t.theater_name
FROM RESERVATIONS r
JOIN SCHEDULES s on r.schedule_id = s.schedule_id
JOIN MOVIES m on s.movie_id = m.movie_id
JOIN THEATERS t on s.theater_id = t.theater_id
        ORDER BY r.reservation_date DESC
    ";

    $reservations = $db->executeQuery($reservation_query, );

    // 예약된 좌석 정보 조회
    $reservedSeats = $db->executeQuery(
        "SELECT ts.seat_row, ts.seat_number, u.user_id
FROM RESERVATION_SEATS rs
JOIN THEATER_SEATS ts ON ts.seat_id = rs.seat_id
JOIN RESERVATIONS r on rs.reservation_id = r.reservation_id
JOIN USERS u on r.user_id = u.user_id
WHERE u.user_id = :user_id
         ORDER BY ts.seat_row, ts.seat_number",
        ['user_id' => $user_id]
    );

    // 리뷰 내역 조회
    $review_query = "
        SELECT r.*, m.title, m.poster
        FROM reviews r
        JOIN movies m ON r.movie_id = m.movie_id
        WHERE r.user_id = :user_id
        ORDER BY r.review_date DESC
    ";
    $reviews = $db->executeQuery($review_query, ['user_id' => $user_id]);

} catch (Exception $e) {
    echo "<script>alert('데이터 조회 중 오류가 발생했습니다: " . htmlentities($e->getMessage(), ENT_QUOTES) . "');</script>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>마이페이지</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200">
<div class="container mx-auto p-6">
    <div class="bg-white rounded-xl shadow-xl p-8">
        <h2 class="text-3xl text-center font-bold text-gray-800 mb-8 pb-4 border-b-2 border-red-500">마이 페이지</h2>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- 왼쪽 프로필 섹션 -->
            <div class="lg:w-1/3">
                <div class="bg-white rounded-lg p-6">
                    <div class="flex flex-col items-center mb-6">
                        <img src="https://i.postimg.cc/5yCBHyZM/istockphoto-1224576073-612x612.jpg"
                             alt="프로필 이미지"
                             class="w-32 h-32 rounded-full border-4 border-red-500 object-cover mb-4">
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($profile[0]['NICKNAME'] ?? '닉네임-미설정'); ?></h3>
                        <p class="text-gray-600"><?php echo htmlspecialchars($profile[0]['EMAIL']); ?></p>
                    </div>

                    <div class="space-y-4">
                        <div class="flex flex-col">
                            <span class="text-gray-600 font-medium">전화번호</span>
                            <span class="text-gray-800"><?php echo htmlspecialchars($profile[0]['PHONE_NUMBER']); ?></span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-gray-600 font-medium">선호 장르</span>
                            <span class="text-gray-800"><?php echo htmlspecialchars($profile[0]['GENRE_NAME'] ?? '미설정'); ?></span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-gray-600 font-medium">가입일</span>
                            <span class="text-gray-800"><?php echo date('Y-m-d', strtotime($profile[0]['CREATED_AT'])); ?></span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-gray-600 font-medium">알림 설정</span>
                            <span class="text-gray-800"><?php echo $profile[0]['NOTIFICATION_ENABLED'] == 'Y' ? '켜짐' : '꺼짐'; ?></span>
                        </div>
                    </div>

                    <button onclick="location.href='edit_profile.php'"
                            class="mt-6 w-full bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg transition-colors duration-300">
                        프로필 수정
                    </button>
                </div>
            </div>

            <!-- 오른쪽 섹션 (예약 내역 + 리뷰) -->
            <div class="lg:w-2/3 space-y-8">
                <!-- 예약 내역 -->
                <div class="bg-white rounded-lg border-b-2 border-red-500">
                    <h3 class="text-xl text-center font-bold text-gray-800 mb-4">예약 내역</h3>
                    <div class="space-y-4">
                        <?php if (!empty($reservations)): ?>
                            <?php foreach ($reservations as $reservation): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-300">
                                    <h4 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($reservation['TITLE']); ?></h4>
                                    <div class="mt-2 space-y-1 text-gray-600">
                                        <p><span class="font-medium">극장:</span> <?php echo htmlspecialchars($reservation['THEATER_NAME']); ?></p>
                                        <p><span class="font-medium">상영 시간:</span> <?php echo date('Y-m-d H:i', strtotime($reservation['START_TIME'])); ?></p>
                                        <p><span class="font-medium">좌석:</span> </p>
                                        <?php foreach ($reservedSeats as $seat): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?= htmlspecialchars($seat['SEAT_ROW']) ?><?= htmlspecialchars($seat['SEAT_NUMBER']) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <p>
                                            <span class="font-medium">예약 상태:</span>
                                            <span class="<?php echo $reservation['STATUS'] == 'Confirmed' ? 'text-green-600' : 'text-red-600'; ?> font-medium">
                                                    <?php echo $reservation['STATUS'] == 'Confirmed' ? '예약 완료' : '취소됨'; ?>
                                                </span>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-4">예약 내역이 없습니다.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 리뷰 섹션 -->
                <div class="bg-white rounded-lg border-b-2 border-red-500">
                    <h3 class="text-xl text-center font-bold text-gray-800 mb-4">내가 작성한 리뷰</h3>
                    <div class="space-y-4">
                        <?php if (!empty($reviews)): ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-300">
                                    <h4 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($review['TITLE']); ?></h4>
                                    <div class="text-yellow-400 my-2">
                                        <?php for ($i = 0; $i < $review['RATING']; $i++) echo '★'; ?>
                                        <?php for ($i = $review['RATING']; $i < 5; $i++) echo '☆'; ?>
                                    </div>
                                    <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($review['REVIEW_TEXT']); ?></p>
                                    <div class="mt-4 flex items-center justify-between">
                                            <span class="text-sm text-gray-500">
                                                작성일: <?php echo date('Y-m-d H:i', strtotime($review['REVIEW_DATE'])); ?>
                                            </span>
                                        <button onclick="location.href='edit_review.php?review_id=<?php echo $review['REVIEW_ID']; ?>'"
                                                class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition-colors duration-300">
                                            리뷰 수정
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-4">작성한 리뷰가 없습니다.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>