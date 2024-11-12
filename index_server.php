<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

// SSH 터널링 설정
$ssh_host = '203.249.87.58'; // SSH 서버 주소
$ssh_port = 20022;              // SSH 포트
$ssh_user = '502_team1';
$ssh_pass = 'data5021';

// Oracle DB 설정
$oracle_host = '203.249.87.57';  // SSH 터널을 통해 localhost로 접속
$oracle_port = 1521;
$local_port = 15211;
$oracle_service = 'orcl';
$db_username = 'DB502_PROJ_G1';
$db_password = '1234';
//
//// SSH 터널 생성
//if (!function_exists('ssh2_connect')) {
//    die('SSH2 확장모듈이 설치되어 있지 않습니다.');
//}
//
//// SSH 연결
//$ssh = ssh2_connect($ssh_host, $ssh_port);
//if (!$ssh) {
//    die('SSH 서버 연결 실패');
//}
//
//// SSH 인증
//if (!ssh2_auth_password($ssh, $ssh_user, $ssh_pass)) {
//    die('SSH 인증 실패');
//}
//
//// 로컬 포트 포워딩 설정 (터널링)
////if (!ssh2_tunnel($ssh, localhost, 15210)) {
////    die('SSH 터널링 실패');
////}
//
//// 포트포워딩 명령어 실행
//$command = "nc -l 15211 & socat TCP-LISTEN:$local_port,fork TCP:$oracle_host:$oracle_port";
//$stream = ssh2_exec($ssh, $command);
//if (!$stream) {
//    die('포트포워딩 명령어 실행 실패');
//}
//
//// 스트림 설정
//stream_set_blocking($stream, true);
//$stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
//$stream_err = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
//
//// 잠시 대기 (포트포워딩이 설정될 때까지)
//sleep(1);
//

// Oracle TNS 접속 문자열
$tns = "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP)(HOST = localhost)(PORT = $local_port))
        (CONNECT_DATA = (SERVICE_NAME = $oracle_service)))";

// Oracle DB 연결
$connect = oci_connect($db_username, $db_password, $tns);
if (!$connect) {
    $e = oci_error();
    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
}

// SQL 쿼리 실행
$sql = "SELECT * FROM THEATERS";
$stid = oci_parse($connect, $sql);
oci_execute($stid);

// 결과 출력
// 테이블 헤더 추가
echo "<table width='100%' border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr>
        <th>영화ID</th>
        <th>제목</th>
        <th>설명</th>
        <th>장르</th>
        <th>상영시간</th>
        <th>개봉일</th>
        <th>관람등급</th>
        <th>감독</th>
        <th>출연진</th>
        <th>포스터</th>
      </tr>\n";

while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    echo "<tr>\n";
    echo "<td>" . ($row['MOVIE_ID'] !== NULL ? htmlspecialchars($row['MOVIE_ID']) : "-") . "</td>\n";
    echo "<td>" . ($row['TITLE'] !== NULL ? htmlspecialchars($row['TITLE']) : "-") . "</td>\n";
    echo "<td>" . ($row['DESCRIPTION'] !== NULL ? htmlspecialchars(substr($row['DESCRIPTION'], 0, 100)) . "..." : "-") . "</td>\n";
    echo "<td>" . ($row['GENRE'] !== NULL ? htmlspecialchars($row['GENRE']) : "-") . "</td>\n";
    echo "<td>" . ($row['DURATION'] !== NULL ? htmlspecialchars($row['DURATION']) . "분" : "-") . "</td>\n";
    echo "<td>" . ($row['RELEASE_DATE'] !== NULL ? htmlspecialchars(date('Y-m-d', strtotime($row['RELEASE_DATE']))) : "-") . "</td>\n";
    echo "<td>" . ($row['AGE_RATING'] !== NULL ? htmlspecialchars($row['AGE_RATING']) : "-") . "</td>\n";
    echo "<td>" . ($row['DIRECTOR'] !== NULL ? htmlspecialchars($row['DIRECTOR']) : "-") . "</td>\n";
    echo "<td>" . ($row['CAST'] !== NULL ? htmlspecialchars($row['CAST']) : "-") . "</td>\n";
    echo "<td>" . ($row['POSTER'] !== NULL ? htmlspecialchars($row['POSTER']) : "이미지 없음") . "</td>\n";
    echo "</tr>\n";
}
echo "</table>\n";

// 스타일 추가
echo "<style>
    table { 
        border-collapse: collapse;
        width: 100%;
        margin: 20px 0;
        font-size: 14px;
    }
    th, td { 
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }
    th { 
        background-color: #f4f4f4;
        font-weight: bold;
    }
    tr:nth-child(even) { 
        background-color: #f9f9f9;
    }
    tr:hover { 
        background-color: #f5f5f5;
    }
</style>";

// 연결 종료
oci_free_statement($stid);
oci_close($connect);
?>