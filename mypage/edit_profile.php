<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('로그인이 필요한 서비스입니다.'); window.location.href='../auth/index.php';</script>";
    exit;
}

try {
    $db = DatabaseConnection::getInstance();
    $user_id = $_SESSION['user_id'];

    // 장르 목록 가져오기
    $genres_query = "SELECT genre_id, genre_name FROM genres ORDER BY genre_name";
    $genres = $db->executeQuery($genres_query);

    // 현재 프로필 정보 가져오기
    $profile_query = "
        SELECT p.*, u.email, u.phone_number
        FROM profiles p
        RIGHT JOIN users u ON p.user_id = u.user_id
        WHERE u.user_id = :user_id
    ";
    $profile = $db->executeQuery($profile_query, ['user_id' => $user_id]);

    // POST 요청 처리
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nickname = $_POST['nickname'];
        $phone_number = $_POST['phone_number'];
        $preferred_genre = $_POST['preferred_genre'];
        $intro = $_POST['intro'];
        $notification = isset($_POST['notification']) ? 'Y' : 'N';

        try {
            // 프로필이 있는지 확인
            $check_query = "SELECT 1 FROM profiles WHERE user_id = :user_id";
            $exists = $db->executeQuery($check_query, ['user_id' => $user_id]);
            if (!empty($exists)) {
                // 프로필 업데이트
                $update_query = "
                    UPDATE profiles 
                    SET nickname = :nickname,
                        intro = :intro,
                        preferred_genre_id = :preferred_genre,
                        notification_enabled = :notification,
                        updated_at = SYSTIMESTAMP
                    WHERE user_id = :user_id
                ";
            } else {
                // 새 프로필 생성
                $update_query = "
                    INSERT INTO profiles (
                        user_id, nickname, intro, preferred_genre_id, 
                        notification_enabled, updated_at
                    ) VALUES (
                        :user_id, :nickname, :intro, :preferred_genre, 
                        :notification, SYSTIMESTAMP
                    )
                ";
            }

            // 프로필 정보 업데이트/생성
            $db->executeNonQuery($update_query, [
                'user_id' => $user_id,
                'nickname' => $nickname,
                'intro' => $intro,
                'preferred_genre' => $preferred_genre,
                'notification' => $notification
            ]);

            // 전화번호 업데이트
            $phone_update_query = "
                UPDATE users 
                SET phone_number = :phone_number 
                WHERE user_id = :user_id
            ";
            $db->executeNonQuery($phone_update_query, [
                'user_id' => $user_id,
                'phone_number' => $phone_number
            ]);

            echo "<script>alert('프로필이 성공적으로 업데이트되었습니다.'); window.location.href='index.php';</script>";
            exit;
        } catch (Exception $e) {
            echo "<script>alert('프로필 업데이트 중 오류가 발생했습니다: " . htmlentities($e->getMessage(), ENT_QUOTES) . "');</script>";
        }
    }

} catch (Exception $e) {
    echo "<script>alert('데이터 조회 중 오류가 발생했습니다: " . htmlentities($e->getMessage(), ENT_QUOTES) . "');</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>프로필 수정</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8 max-w-2xl">
    <div class="bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-8 pb-4 border-b-2 border-red-500">프로필 수정</h2>

        <form method="post" action="" enctype="multipart/form-data" class="space-y-6">

            <!-- 이메일 필드 -->
            <div class="space-y-2">
                <label for="email" class="block text-sm font-medium text-gray-700">이메일</label>
                <input type="text" id="email"
                       value="<?php echo htmlspecialchars($profile[0]['EMAIL']); ?>"
                       disabled
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-500">
            </div>

            <!-- 닉네임 필드 -->
            <div class="space-y-2">
                <label for="nickname" class="block text-sm font-medium text-gray-700">닉네임</label>
                <input type="text" id="nickname" name="nickname"
                       value="<?php echo htmlspecialchars($profile[0]['NICKNAME'] ?? ''); ?>"
                       required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>

            <!-- 전화번호 필드 -->
            <div class="space-y-2">
                <label for="phone" class="block text-sm font-medium text-gray-700">전화번호</label>
                <input type="tel" id="phone" name="phone_number"
                       value="<?php echo htmlspecialchars($profile[0]['PHONE_NUMBER']); ?>"
                       required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
            </div>

            <!-- 선호 장르 선택 -->
            <div class="space-y-2">
                <label for="preferred_genre" class="block text-sm font-medium text-gray-700">선호 장르</label>
                <select id="preferred_genre" name="preferred_genre"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                    <option value="">선택하세요</option>
                    <?php foreach ($genres as $genre): ?>
                        <option value="<?php echo $genre['GENRE_ID']; ?>"
                            <?php echo ($profile[0]['PREFERRED_GENRE_ID'] == $genre['GENRE_ID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($genre['GENRE_NAME']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 자기소개 필드 -->
            <div class="space-y-2">
                <label for="intro" class="block text-sm font-medium text-gray-700">자기소개</label>
                <textarea id="intro" name="intro" rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"><?php echo htmlspecialchars($profile[0]['INTRO'] ?? ''); ?></textarea>
            </div>

            <!-- 알림 설정 -->
            <div class="flex items-center">
                <input type="checkbox" id="notification" name="notification" value="Y"
                    <?php echo ($profile[0]['NOTIFICATION_ENABLED'] ?? 'N') == 'Y' ? 'checked' : ''; ?>
                       class="h-4 w-4 text-red-500 focus:ring-red-500 border-gray-300 rounded">
                <label for="notification" class="ml-2 block text-sm text-gray-700">알림 받기</label>
            </div>

            <!-- 버튼 그룹 -->
            <div class="flex justify-center space-x-4 pt-6">
                <button type="submit"
                        class="px-6 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                    저장하기
                </button>
                <button type="button"
                        onclick="location.href='index.php'"
                        class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                    취소
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function previewImage(event) {
        const reader = new FileReader();
        reader.onload = function() {
            const preview = document.getElementById('preview-image');
            preview.src = reader.result;
        }
        reader.readAsDataURL(event.target.files[0]);
    }
</script>
</body>
</html>