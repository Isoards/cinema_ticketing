<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: review.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $db = DatabaseConnection::getInstance();

    // 리뷰 ID 생성 (시퀀스 사용)
    $review_id = $db->getNextSequenceValue('REVIEW_SEQ');

    // 데이터 삽입
    $sql = "INSERT INTO REVIEWS (review_id, user_id, movie_id, rating, review_text) 
            VALUES (:review_id, :user_id, :movie_id, :rating, :review_text)";

    $params = [
        'review_id' => $review_id,
        'user_id' => $_SESSION['user_id'],
        'movie_id' => $_POST['movie_id'],
        'rating' => $_POST['rating'],
        'review_text' => $_POST['review_text']
    ];

    $db->executeNonQuery($sql, $params);

    // 성공시 리뷰 목록으로 리다이렉트
    header('Location: index.php');
    exit;

} catch (Exception $e) {
    // 오류 발생시 처리
    $_SESSION['error'] = "리뷰 등록 중 오류가 발생했습니다: " . $e->getMessage();
    header('Location: write_review.php');
    exit;
}
?>