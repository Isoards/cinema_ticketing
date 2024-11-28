
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>리뷰 작성</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-6">리뷰 작성</h1>

        <form action="process_review.php" method="POST" class="bg-white rounded-lg shadow p-6">
            <!-- 영화 선택 -->
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="movie">
                    영화 선택
                </label>
                <select name="movie_id" id="movie" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">영화를 선택하세요</option>
                    <option value="1">듄: 파트2</option>
                    <option value="2">웡카</option>
                    <option value="3">데드풀 3</option>
                </select>
            </div>

            <!-- 평점 -->
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    평점
                </label>
                <div class="flex gap-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="rating" value="5" class="form-radio" checked>
                        <span class="ml-2">★★★★★</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="rating" value="4" class="form-radio">
                        <span class="ml-2">★★★★</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="rating" value="3" class="form-radio">
                        <span class="ml-2">★★★</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="rating" value="2" class="form-radio">
                        <span class="ml-2">★★</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="rating" value="1" class="form-radio">
                        <span class="ml-2">★</span>
                    </label>
                </div>
            </div>

            <!-- 제목 -->
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="title">
                    제목
                </label>
                <input type="text" name="title" id="title" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="제목을 입력하세요">
            </div>

            <!-- 내용 -->
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="content">
                    내용
                </label>
                <textarea name="content" id="content" rows="6" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="리뷰 내용을 입력하세요"></textarea>
            </div>

            <!-- 버튼 -->
            <div class="flex justify-end gap-4">
                <a href="review.php"
                   class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    취소
                </a>
                <button type="submit"
                        class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                    등록
                </button>
            </div>
        </form>
    </div>
</div>
</body>
</html>