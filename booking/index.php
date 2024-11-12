<?php
require_once '../includes/db_connect.php';
require_once '../includes/header.php';
require_once '../includes/functions.php';
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
                "SELECT s.*, t.theater_name, m.title, m.running_time, (SELECT COUNT(*)
                                                      FROM SCHEDULE_SEATS ss
                                                      WHERE ss.schedule_id = s.schedule_id
                                                      AND ss.status = 'AVAILABLE') as available_seats
                FROM SCHEDULES s
                JOIN THEATERS t ON s.theater_id = t.theater_id
                JOIN MOVIES m ON s.movie_id = m.movie_id
                WHERE s.theater_id = t.theater_id
                AND s.movie_id = m.movie_id"
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
</head>
<body class="bg-gray-100">

<?php if (isset($error_message)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
    </div>
<?php endif; ?>

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
                                <a href="?theater=<?= $theater['THEATER_ID'] ?>&location=<?= urlencode($theater['LOCATION']) ?>"
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
                            <img src="<?= getMoviePosterPath($movie['POSTER']) ?>"
                                 alt="<?= htmlspecialchars($movie['TITLE']) ?>"
                                 class="w-16 h-24 object-cover">
                        <?php else: ?>
                            <div class="w-16 h-24 bg-gray-300"></div>
                        <?php endif; ?>
                        <div>
                            <h3 class="font-bold"><?= htmlspecialchars($movie['TITLE']) ?></h3>
                            <p class="text-sm text-gray-600">
                                <?= $movie['AGE_RATING'] ?> | <?= $movie['RUNNING_TIME'] ?>분 | <?= $movie['GENRES'] ?>
                                <?php if ($movie['AVG_RATING']): ?>
                                    | ★ <?= number_format($movie['AVG_RATING'], 1) ?>
                                    (<?= $movie['REVIEW_COUNT'] ?>)
                                <?php endif; ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                감독: <?= htmlspecialchars($movie['DIRECTOR']) ?>
                            </p>
                        </div>
                        <a href="?movie=<?= $movie['MOVIE_ID'] ?><?= isset($_GET['theater']) ? '&theater='.$_GET['theater'] : '' ?><?= isset($_GET['location']) ? '&location='.urlencode($_GET['location']) : '' ?>"
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
            <h2 class="text-xl font-bold mb-4">상영 시간 선택</h2>

            <!-- 날짜 선택 -->
            <div class="flex space-x-4 mb-4">
                <?php
                for($i = 0; $i < 7; $i++) {
                    $date = date('Y-m-d', strtotime("+$i days"));
                    $displayDate = date('m/d', strtotime("+$i days"));
                    $dayOfWeek = date('w', strtotime("+$i days"));
                    $weekDay = ['일', '월', '화', '수', '목', '금', '토'][$dayOfWeek];
                    $isSelected = isset($_GET['date']) && $_GET['date'] === $date;
                    $isWeekend = $dayOfWeek == 0 || $dayOfWeek == 6;
                    ?>
                    <a href="?theater=<?= $_GET['theater'] ?>&movie=<?= $_GET['movie'] ?>&date=<?= $date ?>&location=<?= urlencode($_GET['location']) ?>"
                       class="text-center p-2 rounded <?= $isSelected ? 'bg-red-500 text-white' : ($isWeekend ? 'text-red-500' : '') ?>">
                        <div class="font-bold"><?= $displayDate ?></div>
                        <div class="text-sm"><?= $weekDay ?></div>
                    </a>
                <?php } ?>
            </div>

            <!-- 시간표 -->
            <?php
            $selectedDate = $_GET['date'] ?? date('Y-m-d');
            $schedules = getSchedules($_GET['theater'], $_GET['movie'], $selectedDate);
            if (count($schedules) > 0):
                ?>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <?php foreach($schedules as $schedule): ?>
                        <a href="seat.php?schedule=<?= $schedule['SCHEDULE_ID'] ?>"
                           class="p-4 border rounded hover:bg-gray-50">
                            <div class="font-bold">
                                <?= date('H:i', strtotime($schedule['START_TIME'])) ?>
                            </div>
                            <div class="text-sm text-gray-600">
                                잔여 <?= $schedule['AVAILABLE_SEATS'] ?>석
                            </div>
                            <div class="text-sm text-gray-600">
                                <?= date('Y-m-d H:i', strtotime($schedule['START_TIME'])) ?> ~ <?= date('Y-m-d H:i', strtotime($schedule['END_TIME'])) ?>
                            </div>

                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-4">
                    선택하신 날짜에 상영 일정이 없습니다.
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>

</body>
</html>