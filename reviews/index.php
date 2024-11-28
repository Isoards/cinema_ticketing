<?php
require_once '../includes/header.php';

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>영화 리뷰 게시판</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
    <!-- 헤더 섹션 -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">영화 리뷰</h1>
        <a href="write_review.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
            리뷰 작성
        </a>
    </div>

    <!-- 검색 섹션 -->
    <div class="mb-6 bg-white p-4 rounded-lg shadow">
        <form action="" method="GET" class="flex gap-4">
            <select name="search_type" class="flex-none w-32 rounded border-gray-300">
                <option value="title">영화 제목</option>
                <option value="content">리뷰 내용</option>
                <option value="writer">작성자</option>
            </select>
            <input type="text" name="search_keyword"
                   class="flex-grow px-4 py-2 rounded border border-gray-300"
                   placeholder="검색어를 입력하세요">
            <button type="submit"
                    class="flex-none bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                검색
            </button>
        </form>
    </div>

    <!-- 리뷰 목록 -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">번호</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">영화</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">제목</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">평점</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">작성자</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">작성일</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">조회</th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <!-- 샘플 데이터 -->
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="w-10 h-14 bg-gray-300 rounded flex-shrink-0"></div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">듄: 파트2</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900">역대급 영상미와 스케일!</div>
                    <div class="text-sm text-gray-500">댓글 5</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">★★★★★</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">홍길동</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2024-03-19</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">42</td>
            </tr>
            <!-- 추가 샘플 행... -->
            </tbody>
        </table>
    </div>

    <!-- 페이지네이션 -->
    <div class="mt-6 flex justify-center">
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
            <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                이전
            </a>
            <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                1
            </a>
            <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600 hover:bg-blue-100">
                2
            </a>
            <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                3
            </a>
            <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                다음
            </a>
        </nav>
    </div>
</div>
</body>
</html>