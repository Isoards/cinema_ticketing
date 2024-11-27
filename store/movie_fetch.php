<?php
require_once '../config/db_connect.php';

function getBoxOfficeMovies() {
    $key = "7b1ba5c86b5b6102b2f78cc18f64325a";
    $targetDt = '20241125';
    $url = "http://kobis.or.kr/kobisopenapi/webservice/rest/boxoffice/searchDailyBoxOfficeList.json?key={$key}&targetDt={$targetDt}";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
function getMovieDetail($movieData) {
    // 1. URL 인코딩 처리
    $title = urlencode($movieData['movieNm']);
    $releaseDts = $movieData['openDt']; //str_replace('-', '', $movieData['openDt']);
    $key = 'K92U7B96X1K4PK0VD492';

    // 2. URL 생성 및 확인
    $url = "http://api.koreafilm.or.kr/openapi-data2/wisenut/search_api/search_json2.jsp?"
    ."collection=kmdb_new2"
        ."&detail=Y"
        ."&title={$title}"
        ."&ServiceKey={$key}"
        ."&releaseDts={$releaseDts}";

    echo "요청 URL: " . $url . "\n\n";

    $ch = curl_init();

    // 3. CURL 옵션 설정
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // 4. API 호출 및 응답 받기
    $response = curl_exec($ch);

    // 5. CURL 에러 체크
    if(curl_errno($ch)) {
        echo "CURL Error: " . curl_error($ch) . "\n";
    }
    curl_close($ch);
    $data = json_decode($response, true);

    if (isset($data['Data'][0]['Result'][0])) {
        $movieInfo = $data['Data'][0]['Result'][0];

        // 배우 정보 추출
        $actors = [];
        if (isset($movieInfo['actors']['actor'])) {
            foreach ($movieInfo['actors']['actor'] as $actor) {
                $actors[] = $actor['actorNm'];
            }
        }

        // 감독 정보 추출
        $directors = [];
        if (isset($movieInfo['directors']['director'])) {
            foreach ($movieInfo['directors']['director'] as $director) {
                $directors[] = $director['directorNm'];
            }
        }

        // 줄거리 추출
        $plot = '';
        if (isset($movieInfo['plots']['plot'][0]['plotText'])) {
            $plot = $movieInfo['plots']['plot'][0]['plotText'];
        }

        $posters = '';
        if (isset($movieInfo['posters']['poster'])) {
            $posters = $movieInfo['posters'];
        }

        // 최종 데이터 구성
        return [
            // 여러 개의 공백을 하나의 공백으로 변환하고, 앞뒤 공백도 제거
            'title' => preg_replace('/\s+/', ' ', trim(str_replace(['!HS', '!HE'], '', $movieInfo['title']))),'runtime' => $movieInfo['runtime'] ?? '',
            'rating' => $movieInfo['rating'] ?? '',
            'genre' => $movieInfo['genre'] ?? '',
            'plot' => $plot,
            'actors' => implode(', ', $actors),
            'directors' => implode(', ', $directors),
            'prodYear' => $movieInfo['prodYear'] ?? '',
            'poster' => explode('|', $movieInfo['posters'])[0] ?? '',
            'release_date' => $releaseDts
        ];
    }
    return null;
}

function generateCastId($prefix = 'MC') {
    return $prefix . substr(uniqid(), -8);
}

function convertToAgeRating($rating) {
    $ratingMap = [
        '전체관람가' => 'ALL',
        '12세관람가' => '12+',
        '15세이상관람가' => '15+',
        '청소년관람불가' => '19+'
    ];

    return $ratingMap[$rating] ?? 'ALL';
}

function saveMovieData($movieDetail) {
    try {
        $db = DatabaseConnection::getInstance();

        // MOVIES 테이블에 데이터 저장
        $seqValue = $db->getNextSequenceValue('movie_seq');
        $movieId = 'M' . str_pad($seqValue, 9, '0', STR_PAD_LEFT);

        $movieSql = "INSERT INTO MOVIES (
            movie_id, 
            title, 
            description, 
            running_time, 
            release_date, 
            age_rating, 
            poster
        ) VALUES (
            :movie_id,
            :title, 
            :description, 
            :running_time, 
            TO_DATE(:release_date, 'YYYY-MM-DD'),
            :age_rating, 
            :poster
        )";

        $releaseDate = date('Y-m-d', strtotime($movieDetail['release_date']));

        $movieParams = [
            'movie_id' => $movieId,
            'title' => $movieDetail['title'],
            'description' => $movieDetail['plot'], // VARCHAR2(255) 제한
            'running_time' => intval($movieDetail['runtime']),
            'release_date' => $releaseDate,
            'age_rating' => convertToAgeRating($movieDetail['rating']),
            'poster' => $movieDetail['poster']
        ];

        // 영화 정보 저장
        $db->executeNonQuery($movieSql, $movieParams);

        // 장르 저장
        $genres = explode(',', $movieDetail['genre']);
        foreach ($genres as $genre) {
            $genre = trim($genre);

            // 장르 존재 여부 확인
            $checkSql = "SELECT genre_id FROM GENRES WHERE genre_name = :genre_name";
            $existGenre = $db->executeQuery($checkSql, ['genre_name' => $genre]);

            if (empty($existGenre)) {
                // 새로운 장르인 경우
                $genreSeqValue = $db->getNextSequenceValue('genre_seq');
                $genreId = 'G' . str_pad($genreSeqValue, 9, '0', STR_PAD_LEFT);

                $genreSql = "INSERT INTO GENRES (genre_id, genre_name)
                     VALUES (:genre_id, :genre_name)";

                $db->executeNonQuery($genreSql, [
                    'genre_id' => $genreId,
                    'genre_name' => $genre
                ]);
            } else {
                // 기존 장르인 경우 existGenre에서 genre_id 가져오기
                $genreId = $existGenre[0]['GENRE_ID'];
            }

            // MOVIE_GENRES 테이블에 관계 추가
            $movieGenreSql = "INSERT INTO MOVIE_GENRES (movie_id, genre_id)
                    VALUES (:movie_id, :genre_id)";

            $db->executeNonQuery($movieGenreSql, [
                'movie_id' => $movieId,
                'genre_id' => $genreId
            ]);
        }
        // 3. 배우와 감독 정보 저장
        // 3.1 감독 저장
        $directors = explode(', ', $movieDetail['directors']);

        foreach ($directors as $director) {
            $castSeqValue = $db->getNextSequenceValue('cast_seq');
            $castId = 'MC' . str_pad($castSeqValue, 8, '0', STR_PAD_LEFT);

            $castSql = "INSERT INTO MOVIE_CAST (
                cast_id, movie_id, person_name, role_type
            ) VALUES (
                :cast_id, :movie_id, :person_name, 'DIRECTOR'
            )";

            $db->executeNonQuery($castSql, [
                'cast_id' => $castId,
                'movie_id' => $movieId,
                'person_name' => trim($director)
            ]);
        }

        // 3.2 배우 저장
        $actors = explode(', ', $movieDetail['actors']);
        foreach ($actors as $actor) {
            $castSeqValue = $db->getNextSequenceValue('cast_seq');
            $castId = 'MC' . str_pad($castSeqValue, 8, '0', STR_PAD_LEFT);

            $castSql = "INSERT INTO MOVIE_CAST (
                cast_id, movie_id, person_name, role_type
            ) VALUES (
                :cast_id, :movie_id, :person_name, 'ACTOR'
            )";

            $db->executeNonQuery($castSql, [
                'cast_id' => $castId,
                'movie_id' => $movieId,
                'person_name' => trim($actor)
            ]);
        }

        return true;

    } catch (Exception $e) {
        error_log("Error saving movie data: " . $e->getMessage());
        return false;
    }
}

// 메인 실행 코드
try {
    $movieList = getBoxOfficeMovies();
    if (isset($movieList['boxOfficeResult']) && isset($movieList['boxOfficeResult']['dailyBoxOfficeList'])) {
        foreach ($movieList['boxOfficeResult']['dailyBoxOfficeList'] as $movie) {
            $movieDetail = getMovieDetail($movie);
            if ($movieDetail !== null) {
                try {
                    $movieId = saveMovieData($movieDetail);
                    echo "{$movieDetail['title']} 저장 성공 (ID: $movieId)\n";
                } catch (Exception $e) {
                    echo "{$movieDetail['title']} 저장 실패: {$e->getMessage()}\n";
                    continue;
                }
            }
        }
    }
} catch (Exception $e) {
    echo "전체 처리 중 오류 발생: " . $e->getMessage() . "\n";
}

//
//$movieList = getBoxOfficeMovies();
//$results = array();
//
//if (isset($movieList['boxOfficeResult']) && isset($movieList['boxOfficeResult']['dailyBoxOfficeList'])) {
//    foreach ($movieList['boxOfficeResult']['dailyBoxOfficeList'] as $movie) {
//        $detailInfo[] = getMovieDetail($movie);
//        if ($detailInfo !== null){
//            $results[] = $detailInfo;
//        }
//    }
//}

// 결과 출력
// echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

?>

