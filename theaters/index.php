<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once '../includes/header.php';
require_once '../config/db_connect.php';

try {
    $db = DatabaseConnection::getInstance();

    // 상영관 정보 조회
    $sql = "SELECT THEATER_ID, THEATER_NAME, LOCATION, STATUS FROM theaters";
    $theaters = $db->executeQuery($sql);

} catch (Exception $e) {
    echo "<script>alert('데이터베이스 조회 중 오류가 발생했습니다: " . htmlentities($e->getMessage(), ENT_QUOTES) . "');</script>";
    $theaters = [];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>상영관 목록</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<header class="bg-red-600 text-white py-8 shadow-lg">
    <div class="container mx-auto text-center px-4">
        <h1 class="text-4xl font-bold">영화관 상영관 목록</h1>
        <p class="text-xl mt-3 opacity-90">상영 중인 모든 영화관을 확인하세요!</p>
    </div>
</header>

<main class="container mx-auto mt-12 px-4 mb-12">
    <section class="bg-white rounded-xl shadow-xl p-8">
        <h2 class="text-3xl font-bold mb-8 text-gray-800">상영관 정보</h2>

        <?php if (!empty($theaters)): ?>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border border-gray-200">
                    <thead>
                    <tr class="bg-red-500 text-white">
                        <th class="text-center border px-6 py-3 text-left">상영관 이름</th>
                        <th class="text-center border px-6 py-3 text-left">위치</th>
                        <th class="border px-6 py-3"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($theaters as $theater): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="text-center border px-6 py-4"><?php echo htmlspecialchars($theater['THEATER_NAME']); ?></td>
                            <td class="text-center border px-6 py-4"><?php echo htmlspecialchars($theater['LOCATION']); ?></td>
                            <td class="border px-6 py-4">
                                <a href="theaters_details1.php?theater_id=<?= urlencode($theater['THEATER_ID']) ?>"
                                   class="flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg transition-colors duration-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9l-5 5-5-5" />
                                    </svg>
                                    예약하기
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-500 py-8">상영관 정보가 없습니다.</p>
        <?php endif; ?>
    </section>
</main>

</body>
</html>