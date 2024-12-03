<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: review.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/');
    exit;
}

try {
    $db = DatabaseConnection::getInstance();

    // 리뷰 수정
    $sql = "UPDATE REVIEWS 
            SET movie_id = :movie_id,
                rating = :rating,
                review_text = :review_text,
                review_date = SYSTIMESTAMP
            WHERE review_id = :review_id 
            AND user_id = :user_id";

    $result = $db->executeNonQuery($sql, [
        'review_id' => $_POST['review_id'],
        'movie_id' => $_POST['movie_id'],
        'rating' => $_POST['rating'],
        'review_text' => $_POST['review_text'],
        'user_id' => $_SESSION['user_id']
    ]);

    header('Location: ../mypage/');
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = "리뷰 수정 중 오류가 발생했습니다: " . $e->getMessage();
    header('Location: edit_review.php?id=' . $_POST['review_id']);
    exit;
}
?>