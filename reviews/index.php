<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

try {
    $db = DatabaseConnection::getInstance();

    // 전체 리뷰 수 조회
    $sql_count = "SELECT COUNT(*) AS total FROM REVIEWS";
    $row_count = $db->executeQuery($sql_count)[0];
    $num = $row_count['TOTAL'];

    // 페이지네이션 설정
    $list_num = 10;
    $page_num = 10;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $total_page = ceil($num / $list_num);
    $total_block = ceil($total_page / $page_num);
    $now_block = ceil($page / $page_num);
    $s_page = ($now_block * $page_num) - ($page_num - 1);
    $s_page = max($s_page, 1);
    $e_page = min($now_block * $page_num, $total_page);
    $start = ($page - 1) * $list_num;
    $end_row = $page * $list_num;        // 수정된 부분
    $start_row = $start;                 // 수정된 부분

    // 리뷰 목록 조회
    $sql = "
    SELECT * FROM (
        SELECT a.*, ROWNUM AS rnum FROM (
            SELECT r.review_id, r.rating, r.review_title,
                   TO_CHAR(r.review_date, 'YYYY-MM-DD') as review_date,
                   p.nickname, 
                   m.title as movie_title
            FROM REVIEWS r
            JOIN PROFILES p ON r.user_id = p.user_id
            JOIN MOVIES m ON r.movie_id = m.movie_id
            ORDER BY r.review_date DESC
        ) a WHERE ROWNUM <= :end_row
    ) WHERE rnum > :start_row";


    $reviews = $db->executeQuery($sql, [
        'start_row' => $start_row,
        'end_row' => $end_row
    ]);
    echo "<!-- Debug: Page=$page, Start=$start_row, End=$end_row -->";


} catch (Exception $e) {
    $error_message = "오류가 발생했습니다: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>영화 리뷰 게시판</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
    <!-- 헤더 섹션 -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">영화 리뷰</h1>
        <button onclick="writePost()" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
            리뷰 작성
        </button>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- 리뷰 목록 -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">번호</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">영화</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">제목</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">평점</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">작성자</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">작성일</th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <?php foreach ($reviews as $review): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars($review['REVIEW_ID']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <?php echo htmlspecialchars($review['MOVIE_TITLE']); ?>
                    </td>
                    <td class="px-6 py-4">
                        <a href="review_detail.php?id=<?php echo $review['REVIEW_ID']; ?>"
                           class="text-sm text-gray-900 hover:text-blue-600">
                            <?php echo htmlspecialchars($review['REVIEW_TITLE']); ?>
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-yellow-500">
                            <?php echo str_repeat('★', $review['RATING']) . str_repeat('☆', 5 - $review['RATING']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo htmlspecialchars($review['NICKNAME']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <?php echo date('Y-m-d', strtotime($review['REVIEW_DATE'])); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 페이지네이션 -->
    <div class="mt-6 flex justify-center">
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
            <?php if ($page <= 1): ?>
                <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">이전</span>
            <?php else: ?>
                <a href="?page=<?php echo $page-1; ?>"
                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">이전</a>
            <?php endif; ?>

            <?php for ($print_page = $s_page; $print_page <= $e_page; $print_page++): ?>
                <?php if ($print_page == $page): ?>
                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">
                            <?php echo $print_page; ?>
                        </span>
                <?php else: ?>
                    <a href="?page=<?php echo $print_page; ?>"
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <?php echo $print_page; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page >= $total_page): ?>
                <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">다음</span>
            <?php else: ?>
                <a href="?page=<?php echo $page+1; ?>"
                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">다음</a>
            <?php endif; ?>
        </nav>
    </div>
</div>

<script>
    function writePost() {
        <?php if (!isset($_SESSION['user_id'])): ?>
        alert('로그인이 필요합니다.');
        location.href = 'login.php';
        <?php else: ?>
        location.href = 'write_review.php';
        <?php endif; ?>
    }
</script>
</body>
</html>