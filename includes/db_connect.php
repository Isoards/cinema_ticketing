<?php

$oracle_port = 1521;
$local_port = 15211;
$oracle_service = 'orcl';
$db_username = 'DB502_PROJ_G1';
$db_password = '1234';

class DatabaseConnection {
    private static $instance = null;
    private $conn;
    // 데이터베이스 연결 정보
    private $tns = "(DESCRIPTION =
        (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = 15211)))
        (CONNECT_DATA = (SERVICE_NAME = 'orcl')))"; // XE는 Oracle Express Edition의 기본 서비스명입니다.

    private $username = "DB502_PROJ_G1";
    private $password = "1234";

    private function __construct() {
        try {
            // oci8 드라이버를 사용하여 Oracle DB 연결
            $this->conn = oci_connect(
                $this->username,
                $this->password,
                $this->tns,
                'AL32UTF8'
            );

            if (!$this->conn) {
                $e = oci_error();
                throw new Exception($e['message']);
            }
        } catch (Exception $e) {
            error_log("Connection Error: " . $e->getMessage());
            throw new Exception("데이터베이스 연결에 실패했습니다.");
        }
    }

    // 싱글톤 패턴 구현
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DatabaseConnection();
        }
        return self::$instance;
    }

    // SELECT 쿼리 실행
    public function executeQuery($sql, $params = []) {
        $stmt = oci_parse($this->conn, $sql);

        // 바인드 변수 처리
        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, ":$key", $value);
        }

        $result = oci_execute($stmt);

        if (!$result) {
            $e = oci_error($stmt);
            throw new Exception($e['message']);
        }

        $data = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $data[] = $row;
        }

        oci_free_statement($stmt);
        return $data;
    }

    // INSERT, UPDATE, DELETE 쿼리 실행
    public function executeNonQuery($sql, $params = []) {
        $stmt = oci_parse($this->conn, $sql);

        // 바인드 변수 처리
        foreach ($params as $key => $value) {
            oci_bind_by_name($stmt, ":$key", $value);
        }

        $result = oci_execute($stmt);

        if (!$result) {
            $e = oci_error($stmt);
            throw new Exception($e['message']);
        }

        $rowCount = oci_num_rows($stmt);
        oci_free_statement($stmt);
        return $rowCount;
    }

    // 시퀀스에서 다음 값 가져오기
    public function getNextSequenceValue($sequenceName) {
        $sql = "SELECT $sequenceName.NEXTVAL FROM DUAL";
        $result = $this->executeQuery($sql);
        return $result[0]['NEXTVAL'];
    }

    // 연결 종료
    public function __destruct() {
        if ($this->conn) {
            oci_close($this->conn);
        }
    }
}
