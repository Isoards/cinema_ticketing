<?php
session_start(); // 세션 시작
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>무비핑</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<!-- 상단 네비게이션 바 -->
<nav class="bg-gray-900 text-white">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <!-- 로고 -->
            <div class="flex items-center">
                <a href="/index.php" class="text-2xl font-bold">무비핑</a>
            </div>

            <!-- 메인 메뉴 -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="/booking" class="hover:text-red-500 transition-colors">예매</a>
                <a href="/movies" class="hover:text-red-500 transition-colors">영화</a>
                <a href="/theaters" class="hover:text-red-500 transition-colors">영화관</a>
                <a href="/reviews" class="hover:text-red-500 transition-colors">리뷰</a>
            </div>

            <!-- 사용자 메뉴 -->
            <div class="flex items-center space-x-4">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="/mypage" class="hover:text-red-500 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </a>
                    <a href="#" onclick="handleLogout(event)" class="hover:text-red-500 transition-colors">로그아웃</a>
                    <script>
                        function handleLogout(event) {
                            event.preventDefault();
                            if (confirm('로그아웃 하시겠습니까?')) {
                                fetch('/movie/auth/logout.php')
                                    .then(() => {
                                        alert('로그아웃 되었습니다.');
                                        window.location.href = '../index.php';
                                    });
                            }
                        }
                    </script>
                <?php else: ?>
                    <a href="/movie/auth" class="hover:text-red-500 transition-colors">로그인</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>