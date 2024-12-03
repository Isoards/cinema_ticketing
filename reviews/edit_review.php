<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/');
    exit;
}

try {
    $db = DatabaseConnection::getInstance();

    // 리뷰 ID 확인
    if (!isset($_GET['review_id'])) {
        throw new Exception('리뷰 ID가 필요합니다.');
    }

    $review_id = $_GET['review_id'];

// 첫 번째 쿼리: 리뷰 기본 정보 조회
    $sql_review_basic = "
SELECT r.*
FROM REVIEWS r
WHERE r.review_id = :review_id";

    $review_basic = $db->executeQuery($sql_review_basic, [
        'review_id' => $review_id
    ]);

// 두 번째 쿼리: 영화 정보 조회
    $sql_review_movie = "
SELECT m.title as movie_title
FROM REVIEWS r
JOIN MOVIES m ON r.movie_id = m.movie_id
WHERE r.review_id = :review_id";

    $review_movie = $db->executeQuery($sql_review_movie, [
        'review_id' => $review_id
    ]);

// 두 결과를 합치기
    if (!empty($review_basic) && !empty($review_movie)) {
        $review = array_merge($review_basic[0], $review_movie[0]);

        // 사용자 권한 체크
        if ($review['USER_ID'] !== $_SESSION['user_id']) {
            throw new Exception('해당 리뷰를 수정할 권한이 없습니다.');
        }
    } else {
        throw new Exception('해당 리뷰를 찾을 수 없습니다.');
    }

    // 영화 목록 가져오기
    $sql_movies = "SELECT movie_id, title FROM MOVIES ORDER BY title";
    $movies = $db->executeQuery($sql_movies);

} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>리뷰 수정</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        .star-rating {
            display: inline-flex;
            flex-direction: row-reverse;
            gap: 0.5rem;
        }
        .star-rating input {
            display: none;
        }
        .star-rating label {
            color: #ddd;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.2s ease-in-out;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #fbbf24;
        }
        .review-text {
            resize: none;
            height: 3em;
            line-height: 1.5;
            overflow-y: hidden;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">리뷰 수정</h1>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php else: ?>

            <form action="process_edit_review.php" method="POST" class="bg-white rounded-xl shadow-lg p-8 space-y-8">
                <input type="hidden" name="review_id" value="<?php echo htmlspecialchars($review['REVIEW_ID']); ?>">

                <!-- 영화 선택 -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2" for="movie">
                        영화
                    </label>
                    <div class="relative">
                        <select name="movie_id" id="movie" required
                                class="block w-full rounded-lg border-2 border-red-400 focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 px-4 py-3 appearance-none text-gray-700">
                            <?php foreach ($movies as $movie): ?>
                                <option value="<?php echo htmlspecialchars($movie['MOVIE_ID']); ?>"
                                    <?php echo $movie['MOVIE_ID'] == $review['MOVIE_ID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($movie['TITLE']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- 평점 -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        평점
                    </label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>"
                                <?php echo $i === intval($review['RATING']) ? 'checked' : ''; ?>>
                            <label for="star<?php echo $i; ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- 한줄평 -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2" for="review_text">
                        한줄평
                    </label>
                    <textarea name="review_text" id="review_text" required
                              class="review-text block w-full rounded-lg border-2 border-gray-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 px-4 py-3"
                              placeholder="영화에 대한 한줄평을 작성해주세요"><?php echo htmlspecialchars($review['REVIEW_TEXT']); ?></textarea>
                </div>

                <!-- 버튼 -->
                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" onclick="confirmDelete(<?php echo htmlspecialchars($review['REVIEW_ID']); ?>)"
                            class="px-6 py-3 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors duration-200">
                        삭제
                    </button>
                    <a href="../mypage/"
                       class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors duration-200">
                        취소
                    </a>
                    <button type="submit"
                            class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors duration-200">
                        수정
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    function confirmDelete(reviewId) {
        if (confirm('정말로 이 리뷰를 삭제하시겠습니까?')) {
            location.href = `process_delete_review.php?id=${reviewId}`;
        }
    }
</script>
</body>
</html>