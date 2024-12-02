<?php
require_once '../includes/db_connect.php';
require_once '../includes/header.php';

try {
    $db = DatabaseConnection::getInstance();

    // 지역 목록 가져오기
    function getAreas() {
        global $db;
        return $db->executeQuery(
            "SELECT DISTINCT location FROM THEATERS WHERE status = 'ACTIVE' ORDER BY location"
        );
    }

    // 극장 목록 가져오기
    function getTheaters($location = null) {
        global $db;
        if ($location) {
            return $db->executeQuery(
                "SELECT t.*, 
                        (SELECT COUNT(*) FROM THEATER_SEATS ts WHERE ts.theater_id = t.theater_id) as total_seats 
                 FROM THEATERS t 
                 WHERE t.status = 'ACTIVE' AND t.location = :location",
                ['location' => $location]
            );
        }
        return $db->executeQuery(
            "SELECT t.*, 
                    (SELECT COUNT(*) FROM THEATER_SEATS ts WHERE ts.theater_id = t.theater_id) as total_seats 
             FROM THEATERS t 
             WHERE t.status = 'ACTIVE' 
             ORDER BY t.theater_name"
        );
    }

    // 영화 목록 가져오기
    function getMovies() {
        global $db;
        return $db->executeQuery(
            "SELECT m.*, 
                    (SELECT COUNT(*) FROM REVIEWS r WHERE r.movie_id = m.movie_id) as review_count,
                    (SELECT AVG(rating) FROM REVIEWS r WHERE r.movie_id = m.movie_id) as avg_rating,
                    (SELECT LISTAGG(g.genre_name, ', ') WITHIN GROUP (ORDER BY g.genre_name)
                     FROM MOVIE_GENRES mg 
                     JOIN GENRES g ON mg.genre_id = g.genre_id 
                     WHERE mg.movie_id = m.movie_id) as genres,
                    (SELECT mc.person_name  
                     FROM MOVIE_CAST mc 
                     WHERE mc.movie_id = m.movie_id 
                     AND mc.role_type = 'DIRECTOR' 
                     AND ROWNUM = 1) as director
             FROM MOVIES m
             WHERE m.release_date <= SYSDATE
             ORDER BY m.release_date DESC"
        );
    }

    // 상영 스케줄 가져오기

    function getSchedules($theaterId, $movieId, $date) {
        global $db;
        return $db->executeQuery(
            "SELECT s.*, t.theater_name, m.title, m.running_time, 
                (SELECT COUNT(*)
                 FROM THEATER_SEATS ts
                 WHERE ts.theater_id = s.theater_id) -
                (SELECT COUNT(*)
                 FROM SCHEDULE_SEATS ss
                 WHERE ss.schedule_id = s.schedule_id
                 AND ss.status = 'OCCUPIED') as available_seats
         FROM SCHEDULES s
         JOIN THEATERS t ON s.theater_id = t.theater_id
         JOIN MOVIES m ON s.movie_id = m.movie_id
         WHERE s.theater_id = :theater_id
         AND s.movie_id = :movie_id
         AND TRUNC(s.start_time) = TO_DATE(:selected_date, 'YYYY-MM-DD')",
            [
                'theater_id' => $theaterId,
                'movie_id' => $movieId,
                'selected_date' => $date
            ]
        );
    }

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $error_message = "시스템 오류가 발생했습니다. 잠시 후 다시 시도해주세요.";
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>영화 예매</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
</head>
<body class="bg-gray-100">

<?php if (isset($error_message)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
    </div>
<?php endif; ?>

<?php
// 디버그 출력을 위한 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);



try {
    // 입력값 확인
    $theaterId = $_GET['theater'] ?? null;
    $movieId = $_GET['movie'] ?? null;

    // 극장 ID 확인
    $theaterQuery = "SELECT theater_id FROM THEATERS WHERE theater_id = :theater_id";
    $validTheaterId = $db->executeQuery($theaterQuery, ['theater_id' => $theaterId]);

    // 영화 ID 확인
    $movieQuery = "SELECT movie_id FROM MOVIES WHERE movie_id = :movie_id";
    $validMovieId = $db->executeQuery($movieQuery, ['movie_id' => $movieId]);

    echo "검색 조건:<br>";
    echo "극장 ID: " . htmlspecialchars($theaterId) . "<br>";
    echo "영화 ID: " . htmlspecialchars($movieId) . "<br><br>";

    // DB 연결 확인
    echo "DB 연결 상태: ";
    try {
        $db = DatabaseConnection::getInstance();
        echo "성공<br><br>";
    } catch (Exception $e) {
        echo "실패 - " . $e->getMessage() . "<br><br>";
        throw $e;
    }

    // 쿼리 실행
    echo "바인딩될 파라미터:<br>";
    echo "theater_id: " . htmlspecialchars($theaterId) . "<br>";
    echo "movie_id: " . htmlspecialchars($movieId) . "<br><br>";

    $schedules = $db->executeQuery("SELECT s.*, t.theater_name, m.title
    FROM SCHEDULES s
    JOIN THEATERS t on s.theater_id = t.theater_id
    JOIN MOVIES m on s.movie_id = m.movie_id
    WHERE s.theater_id= :theater_id AND s.movie_id= :movie_id", [
        'theater_id' => $validTheaterId[0]['THEATER_ID'],
        'movie_id' => $validMovieId[0]['MOVIE_ID']
        ]);
        // 결과 상세 출력
        var_dump($schedules);

        $sql = $db->executeQuery("SELECT s.*, t.theater_name, m.title
    FROM SCHEDULES s
    JOIN THEATERS t on s.theater_id = t.theater_id
    JOIN MOVIES m on s.movie_id = m.movie_id",);

    var_dump($sql);


    echo "조회 결과:<br>";
    echo "총 " . count($schedules) . "개의 스케줄이 있습니다.<br><br>";

    if (count($schedules) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr>
                <th>Schedule ID</th>
                <th>극장</th>
                <th>영화</th>
                <th>상영 시작</th>
                <th>상영 종료</th>
                <th>가격 정책</th>
                <th>기본 가격</th>
              </tr>";

        foreach ($schedules as $schedule) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($schedule['SCHEDULE_ID']) . "</td>";
            echo "<td>" . htmlspecialchars($schedule['THEATER_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($schedule['MOVIE_TITLE']) . "</td>";
            echo "<td>" . htmlspecialchars($schedule['START_TIME']) . "</td>";
            echo "<td>" . htmlspecialchars($schedule['END_TIME']) . "</td>";
            echo "<td>" . htmlspecialchars($schedule['CATEGORY_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($schedule['BASE_PRICE']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        // 각 스케줄별 좌석 상태 확인
        foreach ($schedules as $schedule) {
            echo "<br>스케줄 " . htmlspecialchars($schedule['SCHEDULE_ID']) . "의 좌석 상태:<br>";

            $seatQuery = "
                SELECT 
                    COUNT(CASE WHEN ss.status = 'AVAILABLE' THEN 1 END) as available_seats,
                    COUNT(CASE WHEN ss.status = 'OCCUPIED' THEN 1 END) as occupied_seats,
                    COUNT(*) as total_seats
                FROM SCHEDULE_SEATS ss
                WHERE ss.schedule_id = :schedule_id
            ";

            $seatStatus = $db->executeQuery($seatQuery, [
                'schedule_id' => $schedule['SCHEDULE_ID']
            ])[0];

            echo "전체 좌석: " . $seatStatus['TOTAL_SEATS'] . "<br>";
            echo "예약 가능: " . $seatStatus['AVAILABLE_SEATS'] . "<br>";
            echo "예약됨: " . $seatStatus['OCCUPIED_SEATS'] . "<br>";
        }
    } else {
        echo "해당하는 스케줄이 없습니다.";
    }

} catch (Exception $e) {
    echo "오류 발생: " . htmlspecialchars($e->getMessage());
}
?>

<div class="container mx-auto p-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- 극장 선택 섹션 -->
        <div class="bg-white p-4 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">극장 선택</h2>
            <div class="grid grid-cols-2 gap-4">
                <!-- 지역 목록 -->
                <div class="border-r">
                    <h3 class="font-bold mb-2">지역</h3>
                    <ul class="space-y-2">
                        <?php
                        $areas = getAreas();
                        foreach($areas as $area):
                            ?>
                            <li>
                                <a href="?location=<?= urlencode($area['LOCATION']) ?>"
                                   class="block p-2 hover:bg-gray-100 rounded
                                   <?= isset($_GET['location']) && $_GET['location'] === $area['LOCATION'] ? 'bg-red-100' : '' ?>">
                                    <?= htmlspecialchars($area['LOCATION']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- 극장 목록 -->
                <div>
                    <h3 class="font-bold mb-2">극장</h3>
                    <ul class="space-y-2">
                        <?php
                        $theaters = isset($_GET['location']) ?
                            getTheaters($_GET['location']) :
                            getTheaters();
                        foreach($theaters as $theater):
                            ?>
                            <li>
                                <a href="?theater=<?= $theater['THEATER_ID'] ?><?= isset($_GET['movie']) ? '&movie='.$_GET['movie'] : '' ?>"
                                   class="block p-2 hover:bg-gray-100 rounded
   <?= isset($_GET['theater']) && $_GET['theater'] == $theater['THEATER_ID'] ? 'bg-red-100' : '' ?>">
                                    <?= htmlspecialchars($theater['THEATER_NAME']) ?>
                                    <span class="text-sm text-gray-500">
        (<?= $theater['TOTAL_SEATS'] ?>석)
    </span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 영화 선택 섹션 -->
        <div class="bg-white p-4 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">영화 선택</h2>
            <div class="space-y-4">
                <?php
                $movies = getMovies();
                foreach($movies as $movie):
                    ?>
                    <div class="flex items-center space-x-4 p-2 hover:bg-gray-100 rounded
                        <?= isset($_GET['movie']) && $_GET['movie'] == $movie['MOVIE_ID'] ? 'bg-red-100' : '' ?>">
                        <?php if ($movie['POSTER']): ?>
                            <img src="<?= $movie['POSTER'] ?>"
                                 alt="<?= htmlspecialchars($movie['TITLE']) ?>"
                                 class="w-16 h-24 object-cover">
                        <?php else: ?>
                            <div class="w-16 h-24 bg-gray-300"></div>
                        <?php endif; ?>
                        <div>
                            <h3 class="font-bold"><?= htmlspecialchars($movie['TITLE']) ?></h3>
                            <p class="text-sm text-gray-600">
                                <?= $movie['AGE_RATING'] ?> | <?= $movie['RUNNING_TIME'] ?>분 | <?= $movie['GENRES'] ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                감독: <?= htmlspecialchars($movie['DIRECTOR']) ?>
                            </p>
                        </div>
                        <a href="?movie=<?= $movie['MOVIE_ID'] ?><?= isset($_GET['theater']) ? '&theater='.$_GET['theater'] : '' ?>"
                                 class="ml-auto px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                            선택
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- 상영 시간 선택 섹션 -->
    <?php if(isset($_GET['theater']) && isset($_GET['movie'])): ?>
        <div class="mt-4 bg-white p-4 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">상영 시간</h2>

            <?php
            // 향후 7일간의 모든 상영 일정을 가져오는 함수로 수정
            function getAllSchedules($theaterId, $movieId) {
                global $db;
                return $db->executeQuery(
                    "SELECT s.*, t.theater_name, m.title, m.running_time, 
                    (SELECT COUNT(*)
                     FROM THEATER_SEATS ts
                     WHERE ts.theater_id = s.theater_id) -
                    (SELECT COUNT(*)
                     FROM SCHEDULE_SEATS ss
                     WHERE ss.schedule_id = s.schedule_id
                     AND ss.status = 'OCCUPIED') as available_seats,
                    TO_CHAR(s.start_time, 'YYYY-MM-DD') as schedule_date
                FROM SCHEDULES s
                JOIN THEATERS t ON s.theater_id = t.theater_id
                JOIN MOVIES m ON s.movie_id = m.movie_id
                WHERE s.theater_id = :theater_id
                AND s.movie_id = :movie_id
                AND s.start_time BETWEEN SYSDATE AND SYSDATE + 7
                ORDER BY s.start_time",
                    [
                        'theater_id' => $theaterId,
                        'movie_id' => $movieId
                    ]
                );
            }

            $allSchedules = getAllSchedules($_GET['theater'], $_GET['movie']);

            // 날짜별로 스케줄 그룹화
            $groupedSchedules = [];
            foreach ($allSchedules as $schedule) {
                $date = $schedule['SCHEDULE_DATE'];
                if (!isset($groupedSchedules[$date])) {
                    $groupedSchedules[$date] = [];
                }
                $groupedSchedules[$date][] = $schedule;
            }

            if (count($groupedSchedules) > 0):
                foreach ($groupedSchedules as $date => $schedules):
                    $dayOfWeek = date('w', strtotime($date));
                    $weekDay = ['일', '월', '화', '수', '목', '금', '토'][$dayOfWeek];
                    $isWeekend = $dayOfWeek == 0 || $dayOfWeek == 6;
                    ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-bold mb-3 <?= $isWeekend ? 'text-red-500' : '' ?>">
                            <?= date('Y년 m월 d일', strtotime($date)) ?> (<?= $weekDay ?>)
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <?php foreach($schedules as $schedule): ?>
                                <div class="p-4 border rounded hover:bg-gray-50 cursor-pointer schedule-btn"
                                     data-schedule-id="<?= $schedule['SCHEDULE_ID'] ?>">
                                    <div class="font-bold">
                                        <?= date('H:i', strtotime($schedule['START_TIME'])) ?>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        잔여 <?= $schedule['AVAILABLE_SEATS'] ?>석
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        ~<?= date('H:i', strtotime($schedule['END_TIME'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach;
            else: ?>
                <p class="text-center text-gray-500 py-4">
                    예매 가능한 상영 일정이 없습니다.
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const scheduleButtons = document.querySelectorAll('.schedule-btn');

        scheduleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

                if (!isLoggedIn) {
                    Swal.fire({
                        title: '로그인 필요',
                        text: '예매는 로그인 후 이용 가능합니다.',
                        icon: 'warning',
                        confirmButtonText: '로그인하기',
                        confirmButtonColor: '#EF4444',
                        showCancelButton: true,
                        cancelButtonText: '취소'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const returnUrl = encodeURIComponent(window.location.href);
                            window.location.href = '/auth/login.php?redirect=' + returnUrl;
                        }
                    });
                    return;
                }

                const scheduleId = this.getAttribute('data-schedule-id');
                window.location.href = `seat.php?schedule=${scheduleId}`;
            });
        });
    });
</script>

<?php
require_once '../includes/footer.php';
?>

</body>
</html>