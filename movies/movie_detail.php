<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// URL 파라미터에서 movie_id 가져오기
$movie_id = isset($_GET['movie_id']) ? $_GET['movie_id'] : '';

try {
    $db = DatabaseConnection::getInstance();

    // 영화 기본 정보 조회
    $movie = $db->executeQuery(
        "SELECT m.MOVIE_ID, m.TITLE, m.POSTER, m.RELEASE_DATE, 
                m.RUNNING_TIME, m.DESCRIPTION, m.AGE_RATING
         FROM MOVIES m
         WHERE m.MOVIE_ID = :movie_id",
        ['movie_id' => $movie_id]
    );

    if (empty($movie)) {
        throw new Exception("영화를 찾을 수 없습니다.");
    }

    $movie = $movie[0];

    // 장르 정보 조회
    $genres = $db->executeQuery(
        "SELECT g.GENRE_NAME
         FROM MOVIE_GENRES mg
         JOIN GENRES g ON mg.GENRE_ID = g.GENRE_ID
         WHERE mg.MOVIE_ID = :movie_id",
        ['movie_id' => $movie_id]
    );

    // 감독 정보 조회
    $directors = $db->executeQuery(
        "SELECT PERSON_NAME
         FROM MOVIE_CAST
         WHERE MOVIE_ID = :movie_id 
         AND ROLE_TYPE = 'DIRECTOR'",
        ['movie_id' => $movie_id]
    );

    // 배우 정보 조회
    $actors = $db->executeQuery(
        "SELECT PERSON_NAME
         FROM MOVIE_CAST
         WHERE MOVIE_ID = :movie_id 
         AND ROLE_TYPE = 'ACTOR'",
        ['movie_id' => $movie_id]
    );

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $error_message = "시스템 오류가 발생했습니다. 잠시 후 다시 시도해주세요.";
}

// 장르 이름들을 하나의 문자열로 결합
$genre_names = !empty($genres) ? implode(', ', array_column($genres, 'GENRE_NAME')) : '장르 정보 없음';

// 감독 이름들을 하나의 문자열로 결합
$director_names = !empty($directors) ? implode(', ', array_column($directors, 'PERSON_NAME')) : '감독 정보 없음';

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($movie['TITLE']) ? htmlspecialchars($movie['TITLE']) : '영화 상세' ?> - 무비핑</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <?= $error_message ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="md:flex">
                <!-- 영화 포스터 -->
                <div class="md:w-1/3">
                    <img src="<?= $movie['POSTER'] ?>"
                         alt="<?= htmlspecialchars($movie['TITLE']) ?> 포스터"
                         class="w-full h-auto"/>
                </div>

                <!-- 영화 정보 -->
                <div class="md:w-2/3 p-8">
                    <h1 class="text-3xl font-bold mb-4"><?= htmlspecialchars($movie['TITLE']) ?></h1>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <p class="text-gray-600">장르</p>
                            <p class="font-semibold"><?= htmlspecialchars($genre_names) ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600">상영시간</p>
                            <p class="font-semibold"><?= htmlspecialchars($movie['RUNNING_TIME']) ?>분</p>
                        </div>
                        <div>
                            <p class="text-gray-600">개봉일</p>
                            <p class="font-semibold"><?= date('Y-m-d', strtotime($movie['RELEASE_DATE'])) ?></p>
                        </div>
                        <div>
                            <p class="text-gray-600">관람등급</p>
                            <p class="font-semibold"><?= htmlspecialchars($movie['AGE_RATING']) ?></p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h2 class="text-xl font-bold mb-2">줄거리</h2>
                        <p class="text-gray-700 leading-relaxed">
                            <?= nl2br(htmlspecialchars($movie['DESCRIPTION'])) ?>
                        </p>
                    </div>

                    <div class="mb-6">
                        <h2 class="text-xl font-bold mb-2">감독</h2>
                        <p class="text-gray-700"><?= htmlspecialchars($director_names) ?></p>
                    </div>

                    <?php if (!empty($actors)): ?>
                        <div class="mb-6">
                            <h2 class="text-xl font-bold mb-2">출연진</h2>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach ($actors as $actor): ?>
                                    <div class="bg-gray-50 p-3 rounded">
                                        <p class="font-semibold"><?= htmlspecialchars($actor['PERSON_NAME']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button onclick="location.href='/movie/booking/?movie=<?= $movie_id ?>'"
                            class="w-full md:w-auto bg-red-500 text-white px-8 py-3 rounded-lg hover:bg-red-600 transition-colors">
                        예매하기
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
require_once '../includes/footer.php';
?>
</body>
</html>