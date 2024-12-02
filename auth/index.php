<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);

session_start();

require_once '../includes/db_connect.php';

try {
    $db = DatabaseConnection::getInstance();

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['login'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];

            try {
                $result = $db->executeQuery(
                    "SELECT user_id, password FROM users WHERE email = :email",
                    ['email' => $email]
                );

                if (!empty($result)) {
                    $row = $result[0];
                    if (password_verify($password, $row['PASSWORD'])) {
                        $_SESSION['user_id'] = $row['USER_ID'];
                        echo "<script>alert('로그인 성공!');window.location.href='../index.php';</script>";
                    } elseif ($row['PASSWORD'] === $password) {
                        // 기존 비밀번호와 일치하는 경우 (해시되지 않은 비밀번호)
                        $_SESSION['user_id'] = $row['USER_ID'];

                        // 비밀번호를 새로운 해시로 업데이트
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $db->executeNonQuery(
                            "UPDATE users SET password = :new_password WHERE user_id = :user_id",
                            [
                                'new_password' => $new_hash,
                                'user_id' => $row['USER_ID']
                            ]
                        );

                        echo "<script>alert('로그인 성공! 비밀번호가 보안을 위해 업데이트되었습니다.');</script>";
                    } else {
                        echo "<script>alert('이메일 또는 비밀번호가 잘못되었습니다.');</script>";
                    }
                } else {
                    echo "<script>alert('이메일 또는 비밀번호가 잘못되었습니다.');</script>";
                }
            } catch (Exception $e) {
                echo "<script>alert('로그인 처리 중 오류가 발생했습니다: " . htmlentities($e->getMessage(), ENT_QUOTES) . "');</script>";
            }
        } elseif (isset($_POST['register'])) {
            $email = $_POST['email'];
            $password = $_POST['password'];
            $phone = $_POST['phone'];

            try {
                $seq_val = $db->getNextSequenceValue('movie_seq');
                $user_id = 'U' . str_pad($seq_val, 9, '0', STR_PAD_LEFT);
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $result = $db->executeNonQuery(
                    "INSERT INTO users (user_id, email, password, phone_number, created_at) VALUES (:user_id, :email, :password, :phone, SYSTIMESTAMP)",
                    [
                        'user_id' => $user_id,
                        'email' => $email,
                        'password' => $hashed_password,
                        'phone' => $phone
                    ]
                );

                echo "<script>alert('회원가입이 완료되었습니다.');</script>";
            } catch (Exception $e) {
                echo "<script>alert('회원가입 실패: " . htmlentities($e->getMessage(), ENT_QUOTES) . "');</script>";
            }
        }
    }
} catch (Exception $e) {
    echo "<script>alert('데이터베이스 연결 오류: " . htmlentities($e->getMessage(), ENT_QUOTES) . "');</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>영화관 로그인/회원가입</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">
<div class="bg-black bg-opacity-75 p-8 rounded-lg shadow-xl w-96">
    <!-- 로그인 폼 -->
    <div id="login-form" class="space-y-6">
        <div class="flex items-center justify-center space-x-3">
            <h2 class="text-2xl font-bold text-red-500">로그인</h2>
        </div>
        <form method="post" action="" class="space-y-4">
            <input type="email" name="email" placeholder="이메일" required
                   class="w-full px-4 py-2 bg-transparent border-b-2 border-red-500 text-white focus:outline-none">
            <input type="password" name="password" placeholder="비밀번호" required
                   class="w-full px-4 py-2 bg-transparent border-b-2 border-red-500 text-white focus:outline-none">
            <input type="submit" name="login" value="로그인"
                   class="w-full py-2 bg-red-500 hover:bg-red-600 text-white rounded transition-colors duration-300">
        </form>
        <button onclick="switchForm('register')"
                class="w-full py-2 bg-gray-700 hover:bg-gray-600 text-white rounded transition-colors duration-300">회원가입</button>
    </div>

    <!-- 회원가입 폼 -->
    <div id="register-form" class="hidden space-y-6">
        <div class="flex items-center justify-center space-x-3">
            <h2 class="text-2xl font-bold text-red-500">회원가입</h2>
        </div>
        <form method="post" action="" class="space-y-4">
            <input type="email" name="email" placeholder="이메일" required
                   class="w-full px-4 py-2 bg-transparent border-b-2 border-red-500 text-white focus:outline-none">
            <input type="password" name="password" placeholder="비밀번호" required
                   class="w-full px-4 py-2 bg-transparent border-b-2 border-red-500 text-white focus:outline-none">
            <input type="tel" name="phone" placeholder="전화번호" required
                   class="w-full px-4 py-2 bg-transparent border-b-2 border-red-500 text-white focus:outline-none">
            <input type="submit" name="register" value="회원가입"
                   class="w-full py-2 bg-red-500 hover:bg-red-600 text-white rounded transition-colors duration-300">
        </form>
        <button onclick="switchForm('login')"
                class="w-full py-2 bg-gray-700 hover:bg-gray-600 text-white rounded transition-colors duration-300">로그인으로 돌아가기</button>
    </div>
</div>

<script>
    function switchForm(form) {
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');

        if (form === 'register') {
            loginForm.classList.add('hidden');
            registerForm.classList.remove('hidden');
        } else {
            registerForm.classList.add('hidden');
            loginForm.classList.remove('hidden');
        }
    }
</script>
</body>
</html>