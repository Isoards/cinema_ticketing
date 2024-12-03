<?php
require_once '../includes/db_connect.php';
require_once '../includes/header.php';

try {
    $db = DatabaseConnection::getInstance();

    // 지역 목록 SQL
    $areas_sql = "SELECT DISTINCT location FROM THEATERS WHERE status = 'ACTIVE' ORDER BY location";
    $areas = $db->executeQuery($areas_sql);

    // 극장 목록 SQL
    $theaters_sql = isset($_GET['location'])
        ? "SELECT t.*, 
                (SELECT COUNT(*) FROM THEATER_SEATS ts WHERE ts.theater_id = t.theater_id) as total_seats 
           FROM THEATERS t 
           WHERE t.status = 'ACTIVE' AND t.location = :location"
        : "SELECT t.*, 
                (SELECT COUNT(*) FROM THEATER_SEATS ts WHERE ts.theater_id = t.theater_id) as total_seats 
           FROM THEATERS t 
           WHERE t.status = 'ACTIVE' 
           ORDER BY t.theater_name";

    $theaters = isset($_GET['location'])
        ? $db->executeQuery($theaters_sql, ['location' => $_GET['location']])
        : $db->executeQuery($theaters_sql);

    // 선택된 극장이 있는 경우, 해당 극장을 최상단으로 이동
    if (isset($_GET['theater'])) {
        $selected_theater = array_filter($theaters, function($t) {
            return $t['THEATER_ID'] == $_GET['theater'];
        });

        if (!empty($selected_theater)) {
            $other_theaters = array_filter($theaters, function($t) {
                return $t['THEATER_ID'] != $_GET['theater'];
            });
            $theaters = array_merge($selected_theater, $other_theaters);
        }
    }

    // 영화 목록 SQL
    $movies_sql = "SELECT m.*, 
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
    ORDER BY m.release_date DESC";
    $movies = $db->executeQuery($movies_sql);

    // 선택된 영화가 있는 경우, 해당 영화를 최상단으로 이동
    if (isset($_GET['movie'])) {
        $selected_movie = array_filter($movies, function($m) {
            return $m['MOVIE_ID'] == $_GET['movie'];
        });

        if (!empty($selected_movie)) {
            $other_movies = array_filter($movies, function($m) {
                return $m['MOVIE_ID'] != $_GET['movie'];
            });
            $movies = array_merge($selected_movie, $other_movies);
        }
    }

    // 스케줄 처리
    $theaterId = $_GET['theater'] ?? null;
    $movieId = $_GET['movie'] ?? null;
    $schedules = [];

    // 극장 ID 확인
    if ($theaterId) {
        $theater_check_sql = "SELECT theater_id FROM THEATERS WHERE theater_id = :theater_id";
        $validTheater = $db->executeQuery($theater_check_sql, ['theater_id' => $theaterId]);

        if (!empty($validTheater)) {
            $theater_schedules_sql = "SELECT
                s.schedule_id,
                TO_CHAR(s.start_time, 'YYYY-MM-DD HH24:MI:SS') AS start_time, 
                TO_CHAR(s.end_time, 'YYYY-MM-DD HH24:MI:SS') AS end_time,
                t.theater_name,
                m.title,
                m.running_time
                FROM SCHEDULES s
                JOIN THEATERS t on s.theater_id = t.theater_id
                JOIN MOVIES m on s.movie_id = m.movie_id
                WHERE s.theater_id = :theater_id";
            $schedules = $db->executeQuery($theater_schedules_sql,
                ['theater_id' => $validTheater[0]['THEATER_ID']]
            );
        }
    }

    // 영화 ID 확인
    if ($movieId) {
        $movie_check_sql = "SELECT movie_id FROM MOVIES WHERE movie_id = :movie_id";
        $validMovie = $db->executeQuery($movie_check_sql, ['movie_id' => $movieId]);

        if (!empty($validMovie)) {
            $movie_schedules_sql = "SELECT
                s.schedule_id,
                TO_CHAR(s.start_time, 'YYYY-MM-DD HH24:MI:SS') AS start_time, 
                TO_CHAR(s.end_time, 'YYYY-MM-DD HH24:MI:SS') AS end_time,
                t.theater_name,
                m.title,
                m.running_time, (SELECT COUNT(*) FROM SCHEDULE_SEATS ss WHERE ss.schedule_id = s.schedule_id) as schedule_count
                FROM SCHEDULES s
                JOIN THEATERS t on s.theater_id = t.theater_id
                JOIN MOVIES m on s.movie_id = m.movie_id
                WHERE s.movie_id = :movie_id";
            $movie_schedules = $db->executeQuery($movie_schedules_sql,
                ['movie_id' => $validMovie[0]['MOVIE_ID']]
            );

            // 두 결과 합치기
            if (!empty($schedules)) {
                $schedules = array_filter($movie_schedules, function($schedule) use ($schedules) {
                    foreach ($schedules as $theater_schedule) {
                        if ($schedule['SCHEDULE_ID'] === $theater_schedule['SCHEDULE_ID']) {
                            return true;
                        }
                    }
                    return false;
                });
            } else {
                $schedules = $movie_schedules;
            }
        }
    }

    $scheduleId = $_GET['schedules'] ?? null;
    print_r($scheduleId);

    $available_seat_sql = "SELECT COUNT(*)
    FROM SCHEDULES s
    JOIN SCHEDULE_SEATS ss ON ss.schedule_id = s.schedule_id
    WHERE ss.SCHEDULE_ID = s.schedule_id
    AND ss.status = 'OCCUPIED'";


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
                        <?php foreach($areas as $area): ?>
                            <li>
                                <a href="?location=<?= urlencode($area['LOCATION']) ?><?= isset($_GET['movie']) ? '&movie='.$_GET['movie'] : '' ?>"
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
                        <?php foreach($theaters as $theater): ?>
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
                <?php foreach($movies as $movie): ?>
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
            // 날짜별 스케줄 그룹화
            $groupedSchedules = [];
            foreach ($schedules as $schedule) {
                $date = date('Y-m-d', strtotime($schedule['START_TIME']));
                if (!isset($groupedSchedules[$date])) {
                    $groupedSchedules[$date] = [];
                }
                $groupedSchedules[$date][] = $schedule;
            }

            if (count($groupedSchedules) > 0):
                foreach ($groupedSchedules as $date => $daySchedules):
                    $dayOfWeek = date('w', strtotime($date));
                    $weekDay = ['일', '월', '화', '수', '목', '금', '토'][$dayOfWeek];
                    $isWeekend = $dayOfWeek == 0 || $dayOfWeek == 6;
                    ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-bold mb-3 <?= $isWeekend ? 'text-red-500' : '' ?>">
                            <?= date('Y년 m월 d일', strtotime($date)) ?> (<?= $weekDay ?>)
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <?php foreach($daySchedules as $schedule): ?>
                                <div class="p-4 border rounded hover:bg-gray-50 cursor-pointer schedule-btn"
                                     data-schedule-id="<?= $schedule['SCHEDULE_ID'] ?>">
                                    <div class="font-bold">
                                        <?= date('H:i', strtotime($schedule['START_TIME'])) ?>
                                        ~ <?= date('H:i', strtotime($schedule['START_TIME']) + ($schedule['RUNNING_TIME'] * 60)) ?>
                                    </div>
                                    <div class="text-gray-600">
                                        잔여 <?= $theater['TOTAL_SEATS'] - $schedule['SCHEDULE_COUNT'] ?? '0' ?> / <?= $theater['TOTAL_SEATS'] ?> 석
                                    </div>
                                    <div class="text-sm text-gray-600">

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

</body>
</html>