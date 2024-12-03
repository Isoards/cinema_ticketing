<?php
require_once '../includes/header.php';
require_once '../includes/db_connect.php';

try {
    $db = DatabaseConnection::getInstance();
    $theater_details = null;
    $schedule_details = [];

    if (isset($_GET['theater_id'])) {
        $theater_id = $_GET['theater_id'];

        // Get theater details
        $theater_sql = "SELECT THEATER_ID, THEATER_NAME, LOCATION, STATUS FROM theaters WHERE THEATER_ID = :theater_id";
        $theater_details = $db->executeQuery($theater_sql, ['theater_id' => $theater_id])[0];

        // Get schedule details
        $schedule_sql = "
            SELECT 
                s.schedule_id, 
                m.title, 
                m.running_time,
                TO_CHAR(s.start_time, 'YYYY-MM-DD HH24:MI:SS') AS start_time, 
                TO_CHAR(s.end_time, 'YYYY-MM-DD HH24:MI:SS') AS end_time
            FROM schedules s
            JOIN movies m ON s.movie_id = m.movie_id
            WHERE s.theater_id = :theater_id
            ORDER BY s.start_time";

        $schedule_details = $db->executeQuery($schedule_sql, ['theater_id' => $theater_id]);
    } else {
        throw new Exception("상영관 ID가 제공되지 않았습니다.");
    }
} catch (Exception $e) {
    echo "오류가 발생했습니다: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>상영관 상세정보</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
<header class="bg-red-600 text-white py-6 shadow-lg">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-center"><?php echo htmlspecialchars($theater_details['THEATER_NAME']); ?></h1>
    </div>
</header>

<main class="container mx-auto px-4 py-8">
    <!-- Theater Info Section -->
    <section class="bg-white rounded-xl shadow-lg p-8 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-gray-200 rounded-lg aspect-w-16 aspect-h-9 flex items-center justify-center">
                <span class="text-gray-600 text-lg">상영관 이미지</span>
            </div>
            <div class="md:col-span-2">
                <h2 class="text-2xl font-bold mb-6 text-gray-800">상영관 정보</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4 p-4 bg-gray-50 rounded-lg">
                        <div class="font-semibold text-gray-700">상영관 이름</div>
                        <div class="col-span-2"><?php echo htmlspecialchars($theater_details['THEATER_NAME']); ?></div>
                        <div class="font-semibold text-gray-700">위치</div>
                        <div class="col-span-2"><?php echo htmlspecialchars($theater_details['LOCATION']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Calendar Section -->
    <section class="bg-white rounded-lg shadow-lg p-6 my-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">상영 일정</h2>

        <!-- Calendar Header -->
        <div class="flex justify-between items-center mb-4">
            <button id="prev-month" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors duration-200">&lt; 이전</button>
            <div id="current-month" class="text-xl font-bold text-gray-800"></div>
            <button id="next-month" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors duration-200">다음 &gt;</button>
        </div>

        <!-- Calendar Grid -->
        <div id="calendar-grid" class="grid grid-cols-7 gap-2">
            <!-- Calendar will be populated by JavaScript -->
        </div>

        <!-- Schedule List -->
        <div id="schedule-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
            <!-- Schedules will be populated by JavaScript -->
        </div>
    </section>
</main>

<script>
    const scheduleDetails = <?php echo json_encode($schedule_details); ?>;
    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth();

    function renderCalendar() {
        const calendarGrid = document.getElementById("calendar-grid");
        const currentMonthLabel = document.getElementById("current-month");
        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const lastDate = new Date(currentYear, currentMonth + 1, 0).getDate();

        // 해당 월에 스케줄이 있는 날짜들을 Set으로 만듦
        const datesWithSchedule = new Set(
            scheduleDetails
                .map(schedule => {
                    const date = new Date(schedule.START_TIME);
                    if (date.getMonth() === currentMonth && date.getFullYear() === currentYear) {
                        return date.getDate();
                    }
                    return null;
                })
                .filter(date => date !== null)
        );

        calendarGrid.innerHTML = '';

        // Add weekday headers
        const weekdays = ['일', '월', '화', '수', '목', '금', '토'];
        weekdays.forEach(day => {
            const dayElement = document.createElement('div');
            dayElement.className = 'bg-red-500 text-white p-2 text-center font-semibold rounded';
            dayElement.textContent = day;
            calendarGrid.appendChild(dayElement);
        });

        // Add date cells
        for (let i = 1; i <= lastDate; i++) {
            const dateElement = document.createElement('div');
            // 스케줄이 있는 날짜는 다른 스타일 적용
            const hasSchedule = datesWithSchedule.has(i);
            dateElement.className = `p-2 text-center rounded cursor-pointer transition-colors duration-200 ${
                hasSchedule
                    ? 'bg-red-200 hover:bg-red-300 font-semibold'
                    : 'bg-gray-100 hover:bg-red-100'
            }`;
            dateElement.textContent = i;
            dateElement.addEventListener('click', () => loadSchedules(i));
            calendarGrid.appendChild(dateElement);
        }

        currentMonthLabel.textContent = `${currentYear}년 ${currentMonth + 1}월`;
    }

    function loadSchedules(day) {
        const filteredSchedules = scheduleDetails.filter(schedule => {
            const scheduleDate = new Date(schedule.START_TIME);
            return scheduleDate.getDate() === day &&
                scheduleDate.getMonth() === currentMonth &&
                scheduleDate.getFullYear() === currentYear;
        });

        const scheduleList = document.querySelector('#schedule-list');
        scheduleList.innerHTML = '';

        if (filteredSchedules.length > 0) {
            filteredSchedules.forEach(schedule => {
                const scheduleItem = document.createElement('div');
                scheduleItem.className = 'bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition-all duration-200 cursor-pointer';

                scheduleItem.onclick = () => {
                    window.location.href = `/movie/booking/seat.php?schedule=${schedule.SCHEDULE_ID}`;
                };

                scheduleItem.innerHTML = `
                        <div class="text-xl font-bold mb-4 text-gray-800">${schedule.TITLE}</div>
<p class="flex items-center">
    <span class="font-semibold w-24">시작 시간:</span>
    <span>${new Date(schedule.START_TIME).toLocaleString()}</span>
</p>
<p class="flex items-center">
    <span class="font-semibold w-24">종료 시간:</span>
    <span>${new Date(new Date(schedule.START_TIME).getTime() + schedule.RUNNING_TIME * 60000).toLocaleString()}</span>
</p>
                    `;

                scheduleList.appendChild(scheduleItem);
            });
        } else {
            scheduleList.innerHTML = `
                    <div class="col-span-full text-center py-8 bg-gray-50 rounded-lg text-gray-500">
                        선택하신 날짜에 상영 일정이 없습니다.
                    </div>
                `;
        }
    }

    // Event Listeners
    document.getElementById('prev-month').addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar();
    });

    document.getElementById('next-month').addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar();
    });

    // Initial render
    renderCalendar();
</script>
</body>
</html>