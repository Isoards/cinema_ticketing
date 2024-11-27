<?php
require_once '../includes/header.php';
require_once '../config/db_connect.php';
require_once '../includes/functions.php';

try {
    $db = DatabaseConnection::getInstance();

    $movies = $db->executeQuery(
        "SELECT MOVIE_ID, TITLE, POSTER FROM MOVIES",
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
    <title>무비핑</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
<!-- 영화 목록 섹션 -->
<div class="container mx-auto px-4 py-8">
    <h2 class="text-2xl font-bold mb-6">현재 상영작</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <?php foreach ($movies as $movie): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="aspect-w-2 aspect-h-3 bg-gray-200">

                    <img src="<?= $movie['POSTER']?>"
                         alt="<?= htmlspecialchars($movie['TITLE']) ?> 포스터"
                         class="object-cover w-full h-full"/>
                </div>
                <div class="p-4">
                    <h3 class="font-bold mb-2"><?= htmlspecialchars($movie['TITLE']) ?></h3>
                    <button onclick="location.href='/movies/movie_detail.php?movie_id=<?= $movie['MOVIE_ID'] ?>'"
                            class="mt-4 w-full bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition-colors">
                        상세정보
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>


<?php
require_once '../includes/footer.php';
?>

</body>
</html>