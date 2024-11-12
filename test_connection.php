<?php
// test_connection.php

require_once 'db_connect.php';

try {
    $db = new OracleConnection();

    // 버전 정보 확인
    $stmt = $db->executeQuery("SELECT * FROM V$VERSION");
    $versions = $db->fetchAll($stmt);

    echo "<h2>데이터베이스 연결 성공!</h2>";
    echo "<h3>Oracle 버전 정보:</h3>";
    echo "<pre>";
    print_r($versions);
    echo "</pre>";

    // 테이블 목록 확인
    $stmt = $db->executeQuery("
        SELECT table_name 
        FROM user_tables 
        ORDER BY table_name
    ");

    $tables = $db->fetchAll($stmt);

    echo "<h3>사용 가능한 테이블 목록:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table['TABLE_NAME']) . "</li>";
    }
    echo "</ul>";

    // 현재 세션 정보
    $stmt = $db->executeQuery("
        SELECT 
            sys_context('USERENV','CURRENT_USER') as username,
            sys_context('USERENV','DB_NAME') as db_name,
            sys_context('USERENV','HOST') as host,
            sys_context('USERENV','INSTANCE_NAME') as instance
        FROM dual
    ");

    $session = $db->fetchOne($stmt);

    echo "<h3>현재 세션 정보:</h3>";
    echo "<pre>";
    print_r($session);
    echo "</pre>";

} catch (Exception $e) {
    echo "<h2>연결 오류:</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";

    echo "<h3>PHP OCI8 정보:</h3>";
    echo "<pre>";
    print_r(get_loaded_extensions());
    echo "</pre>";
}
?>