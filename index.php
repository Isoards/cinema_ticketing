<?php
require_once 'includes/header.php';
require_once 'includes/db_connect.php';

try {
    $db = DatabaseConnection::getInstance();

    $movies = $db->executeQuery(
        "SELECT * FROM (
        SELECT MOVIE_ID, TITLE, POSTER 
            FROM MOVIES 
            ORDER BY MOVIE_ID DESC
        ) WHERE ROWNUM <= 4",
    );
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
    <title>무비핑 - 영화 예매의 즐거움</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
        }
        .movie-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .movie-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        .hero-text {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }
    </style>
</head>

<body class="bg-indigo-900">
<!-- 메인 배너 섹션 -->
<div class="relative h-screen flex items-center justify-center overflow-hidden group">
    <div class="absolute inset-0 z-0 mx-auto my-auto">
        <iframe
                src="https://www.youtube.com/embed/videoseries?list=PLwQm9_O-zm8a0Rcbj4T85UBI9eZKQvn3Z&autoplay=1&mute=1&loop=1&controls=0&showinfo=0&rel=0&modestbranding=1&iv_load_policy=3&cc_load_policy=0"
                allow="autoplay; encrypted-media"
                allowfullscreen
                class="w-full h-full object-cover"
                style="pointer-events: none;"
        ></iframe>
    </div>
    <div class="absolute inset-0 bg-black opacity-0 group-hover:opacity-50 transition-opacity duration-300 z-10"></div>
    <div class="container mx-auto text-center relative z-20 text-white px-4 py-20 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
        <h1 class="text-6xl font-bold mb-6 opacity-85 hero-text leading-tight">영화의 감동을<br>느껴보세요</h1>
        <p class="text-2xl mb-12 hero-text max-w-3xl opacity-85 mx-auto">오직 무비핑에서만</p>
        <a href="/booking" class="bg-red-600 text-white px-10 opacity-90 py-4 rounded-full text-xl font-semibold hover:bg-red-700 transition-colors inline-block">
            지금 예매하기
        </a>
    </div>
</div>

<!-- 영화 목록 섹션 -->
<div class="container mx-auto px-6 py-16">
    <h2 class="text-3xl font-bold mb-8 text-center text-gray-800">최신 개봉작</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
        <?php foreach ($movies as $movie): ?>
            <div class="movie-card bg-white rounded-lg shadow-md overflow-hidden transition-transform duration-300 ease-in-out transform hover:-translate-y-1 hover:shadow-lg">
                <img src="<?= $movie['POSTER'] ?>"
                     alt="<?= htmlspecialchars($movie['TITLE']) ?> 포스터"
                     class="w-full h-[300px] object-cover"/>
                <div class="p-6 text-center">
                    <h3 class="font-bold text-xl mb-2"><?= htmlspecialchars($movie['TITLE']) ?></h3>
                    <a href="/movie/booking/?movie=<?= $movie['MOVIE_ID'] ?>"
                       class="bg-red-600 text-white block w-full px-4 py-2 rounded-full hover:bg-red-700 transition-colors duration-300 transform hover:scale-105">
                        예매하기
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- 영화 관람 유도 섹션 -->
<div class="bg-gray-900 text-white py-16">
    <div class="container mx-auto px-6 text-center">
        <h2 class="text-3xl font-bold mb-4">영화 관람의 즐거움을 느껴보세요!</h2>
        <p class="text-xl mb-8">최신 영화를 가장 빠르게</p>
        <a href="/booking" class="bg-white text-red-600 px-8 py-3 rounded-full text-lg font-semibold hover:bg-gray-100 transition-colors duration-300 transform hover:scale-105">
            지금 예매하기
        </a>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>

</body>
</html>