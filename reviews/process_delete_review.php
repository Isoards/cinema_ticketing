<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/');
    exit;
}

try {
    $db = DatabaseConnection::getInstance();

    // 리뷰 삭제
    $sql = "DELETE FROM REVIEWS 
            WHERE review_id = :review_id 
            AND user_id = :user_id";

    $result = $db->executeNonQuery($sql, [
        'review_id' => $_GET['id'],
        'user_id' => $_SESSION['user_id']
    ]);

    header('Location: ../mypage/');
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = "리뷰 삭제 중 오류가 발생했습니다: " . $e->getMessage();
    header('Location: ../mypage/');
    exit;
}
?>