<?php

// 파일 업로드 관련 상수 정의
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . "/assets/");
define('POSTER_DIR', UPLOAD_DIR . "posters/");
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

/**
 * 이미지 파일 업로드 함수
 *
 * @param array $file $_FILES['input_name']
 * @param string $directory 업로드할 디렉토리 (posters, profiles 등)
 * @return string 저장된 파일명
 * @throws Exception 업로드 실패시 예외 발생
 */
function uploadImage($file, $directory = 'posters')
{
    try {
        // 업로드 디렉토리 설정
        $targetDir = UPLOAD_DIR . $directory . '/';

        // 디렉토리 존재 확인 및 생성
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // 파일 확장자 검사
        $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ALLOWED_EXTENSIONS)) {
            throw new Exception("허용되지 않는 파일 형식입니다. (jpg, jpeg, png만 가능)");
        }

        // 파일 크기 검사
        if ($file["size"] > MAX_FILE_SIZE) {
            throw new Exception("파일 크기가 너무 큽니다. (최대 5MB)");
        }

        // 고유한 파일명 생성
        $fileName = uniqid() . '.' . $imageFileType;
        $targetPath = $targetDir . $fileName;

        // 이미지 파일 검증
        if (!getimagesize($file["tmp_name"])) {
            throw new Exception("유효한 이미지 파일이 아닙니다.");
        }

        // 파일 업로드
        if (!move_uploaded_file($file["tmp_name"], $targetPath)) {
            throw new Exception("파일 업로드에 실패했습니다.");
        }

        return $fileName;

    } catch (Exception $e) {
        error_log("File upload error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * 이미지 파일 삭제 함수
 *
 * @param string $fileName 파일명
 * @param string $directory 삭제할 디렉토리 (posters, profiles 등)
 * @return bool 삭제 성공 여부
 */
function deleteImage($fileName, $directory = 'posters')
{
    $filePath = UPLOAD_DIR . $directory . '/' . $fileName;
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

/**
 * 이미지 경로 가져오기 함수
 *
 * @param string $fileName 파일명
 * @param string $directory 디렉토리 (posters, profiles 등)
 * @return string 이미지 경로
 */
function getImagePath($fileName, $directory = 'posters')
{
    if (empty($fileName)) {
        return '/assets/' . $directory . '/default.jpg';
    }

    $filePath = '/assets/' . $directory . '/' . $fileName;
    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $filePath)) {
        return '/assets/' . $directory . '/default.jpg';
    }

    return $filePath;
}

/**
 * 파일 확장자 확인 함수
 *
 * @param string $fileName 파일명
 * @return bool 허용된 확장자인지 여부
 */
function isAllowedExtension($fileName)
{
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    return in_array($extension, ALLOWED_EXTENSIONS);
}

function getMoviePosterPath($posterFileName) {
    if (empty($posterFileName)) {
        return '/assets/posters/default.jpg';
    }

    // 실제 파일 존재 여부 확인
    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/assets/posters/' . $posterFileName;
    if (!file_exists($filePath)) {
        return '/assets/posters/default.jpg';
    }

    return '/assets/posters/' . $posterFileName;
}

?>