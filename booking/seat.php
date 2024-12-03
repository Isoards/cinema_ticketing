<?php
require_once  '../includes/header.php';
require_once '../includes/db_connect.php';

try {
    $db = DatabaseConnection::getInstance();

    $scheduleId = $_GET['schedule'] ?? '';
    if (empty($scheduleId)) {
        throw new Exception('상영 일정 정보가 없습니다.');
    }

    // 스케줄 정보 조회
    $scheduleInfo = $db->executeQuery(
        "SELECT s.schedule_id, 
                TO_CHAR(s.start_time, 'YYYY-MM-DD HH24:MI:SS') AS start_time, 
                TO_CHAR(s.end_time, 'YYYY-MM-DD HH24:MI:SS') AS end_time,
                t.theater_id,
                t.theater_name,
                m.movie_id,
                m.title as movie_title,
                m.running_time,
                m.age_rating
         FROM SCHEDULES s
         JOIN THEATERS t ON s.theater_id = t.theater_id
         JOIN MOVIES m ON s.movie_id = m.movie_id
         WHERE s.schedule_id = :schedule_id",
        ['schedule_id' => $scheduleId]
    );

    if (empty($scheduleInfo)) {
        throw new Exception('잘못된 상영 일정입니다.');
    }

    $scheduleInfo = $scheduleInfo[0];
    $theaterId = $scheduleInfo['THEATER_ID'];

    $theaterSeats = $db->executeQuery(
        "SELECT SEAT_ID, SEAT_ROW, SEAT_NUMBER
     FROM THEATER_SEATS 
     WHERE THEATER_ID = :theater_id
     ORDER BY SEAT_ROW, SEAT_NUMBER",
        ['theater_id' => $theaterId]
    );

    $scheduleSeats = $db->executeQuery(
        "SELECT SEAT_ID, STATUS
     FROM SCHEDULE_SEATS 
     WHERE SCHEDULE_ID = :schedule_id",
        ['schedule_id' => $scheduleId]
    );

    $seats = [];
    foreach ($theaterSeats as $seat) {
        $seatStatus = 'AVAILABLE';

        foreach ($scheduleSeats as $scheduleSeat) {
            if ($scheduleSeat['SEAT_ID'] === $seat['SEAT_ID']) {
                $seatStatus = $scheduleSeat['STATUS'];
                break;
            }
        }

        $seats[] = [
            'SEAT_ID' => $seat['SEAT_ID'],
            'SEAT_ROW' => $seat['SEAT_ROW'],
            'SEAT_NUMBER' => $seat['SEAT_NUMBER'],
            'STATUS' => $seatStatus
        ];
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $error_message = "시스템 오류가 발생했습니다. 잠시 후 다시 시도해주세요.";
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>좌석 예약 - <?= htmlspecialchars($scheduleInfo['MOVIE_TITLE'] ?? '') ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        .seating-layout {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }
        .seat-row {
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 8px;
        }
        .row-label {
            width: 30px;
            text-align: center;
            font-weight: bold;
        }
        .seats-container {
            display: flex;
            justify-content: center;
            gap: 8px;
            padding: 5px;
            flex: 1;
        }
        .seat {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            border: 1px solid rgba(0,0,0,0.1);
            background-color: #DBEAFE;
        }
        .seat.occupied {
            background-color: #D1D5DB;
            cursor: not-allowed;
            pointer-events: none;
            opacity: 0.8;
            color: #6B7280;
        }
        .seat.selected {
            background-color: #e02727 !important;
            color: white;
        }
        .seat:not(.occupied):hover {
            transform: scale(1.1);
            transition: all 0.2s;
        }
        .seat-info {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            border: 1px solid rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-100">

<!-- 앱바 -->
<div class="bg-white shadow-md">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <a href="index.php" class="text-gray-700 hover:text-gray-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
            </div>
            <div class="flex items-center">
                <span class="text-xl font-semibold text-gray-900">무비핑</span>
            </div>
            <div class="w-6"></div>
        </div>
    </div>
</div>

<?php if (isset($error_message)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
        <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
    </div>
<?php else: ?>

    <div class="container mx-auto p-4">
        <!-- 영화 정보 -->
        <div class="bg-white p-4 rounded-lg shadow mb-4">
            <h1 class="text-2xl font-bold mb-2"><?= htmlspecialchars($scheduleInfo['MOVIE_TITLE']) ?></h1>
            <div class="text-gray-600">
                <?php
                $start_time = strtotime($scheduleInfo['START_TIME']);
                $end_time = strtotime("+{$scheduleInfo['RUNNING_TIME']} minutes", $start_time);
                ?>
                <p><?= date('Y년 m월 d일 H:i', $start_time) ?> ~
                    <?= date('H:i', $end_time) ?></p>
                <p><?= htmlspecialchars($scheduleInfo['THEATER_NAME']) ?></p>
                <p>상영시간: <?= $scheduleInfo['RUNNING_TIME'] ?>분 | <?= $scheduleInfo['AGE_RATING'] ?></p>
            </div>
        </div>

        <!-- 좌석 선택 -->
        <div class="bg-white p-4 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">좌석 선택</h2>

            <!-- 스크린 -->
            <div class="w-full h-8 bg-gray-300 mb-8 text-center text-gray-600 flex items-center justify-center">
                SCREEN
            </div>

            <!-- 좌석 배치도 -->
            <form id="reservationForm" action="process_reservation.php" method="POST">
                <input type="hidden" name="schedule_id" value="<?= $scheduleId ?>">

                <div class="seating-layout">
                    <?php
                    $currentRow = '';
                    foreach ($seats as $seat) {
                        if ($currentRow !== $seat['SEAT_ROW']) {
                            if ($currentRow !== '') {
                                echo "</div></div>";
                            }
                            $currentRow = $seat['SEAT_ROW'];
                            echo "<div class='seat-row'>";
                            echo "<div class='row-label'>{$seat['SEAT_ROW']}</div>";
                            echo "<div class='seats-container'>";
                        }

                        $seatClass = 'seat';
                        if ($seat['STATUS'] !== 'AVAILABLE') {
                            $seatClass .= ' occupied';
                        }
                        ?>
                        <div class="<?= $seatClass ?>"
                             data-seat-id="<?= $seat['SEAT_ID'] ?>"
                             data-status="<?= $seat['STATUS'] ?>">
                            <?= $seat['SEAT_NUMBER'] ?>
                        </div>
                        <?php
                    }
                    if ($currentRow !== '') {
                        echo "</div></div>";
                    }
                    ?>
                </div>

                <div class="seat-info">
                    <!-- 좌석 범례 -->
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #DBEAFE;"></div>
                            <span>일반</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color" style="background-color: #D1D5DB;"></div>
                            <span>선택불가</span>
                        </div>
                    </div>
                    <!-- 선택한 좌석 정보 -->
                    <div id="selectedSeats" class="mt-4 p-4 bg-gray-50 rounded">
                        <h3 class="font-bold mb-2">선택한 좌석</h3>
                        <div id="seatList" class="mb-2"></div>
                        <div id="totalPrice" class="font-bold"></div>
                    </div>
                </div>

                <button type="submit"
                        class="mt-4 w-full bg-red-500 text-white py-2 px-4 rounded hover:bg-red-600 disabled:bg-gray-300"
                        disabled>
                    예약하기
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectedSeats = new Set();
            const form = document.getElementById('reservationForm');
            const submitButton = form.querySelector('button[type="submit"]');

            document.querySelectorAll('.seat').forEach(seat => {
                if (seat.getAttribute('data-status') === 'AVAILABLE') {
                    seat.addEventListener('click', function() {
                        const seatId = this.getAttribute('data-seat-id');

                        if (selectedSeats.has(seatId)) {
                            selectedSeats.delete(seatId);
                            this.classList.remove('selected');
                        } else {
                            if (selectedSeats.size >= 8) {
                                alert('최대 8좌석까지 선택 가능합니다.');
                                return;
                            }
                            selectedSeats.add(seatId);
                            this.classList.add('selected');
                        }

                        updateSelectedSeatsInfo();
                    });
                }
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (selectedSeats.size === 0) {
                    alert('좌석을 선택해주세요.');
                    return;
                }

                const formData = new FormData(form);
                formData.set('selected_seats', Array.from(selectedSeats).join(','));

                submitButton.disabled = true;
                submitButton.textContent = '예약 처리중...';

                fetch('process_reservation.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('예약이 완료되었습니다.');
                            window.location.href = 'reservation_complete.php?id=' + data.reservation_id;
                        } else {
                            throw new Error(data.message || '예약 처리 중 오류가 발생했습니다.');
                        }
                    })
                    .catch(error => {
                        alert(error.message);
                        submitButton.disabled = false;
                        submitButton.textContent = '예약하기';
                    });
            });

            function updateSelectedSeatsInfo() {
                const seatList = document.getElementById('seatList');
                let selectedSeatsArray = [];

                selectedSeats.forEach(seatId => {
                    const seatElement = document.querySelector(`[data-seat-id="${seatId}"]`);
                    const row = seatElement.closest('.seat-row').querySelector('.row-label').textContent;
                    const number = seatElement.textContent;
                    selectedSeatsArray.push(`${row}${number}`);
                });

                seatList.textContent = selectedSeatsArray.length > 0 ?
                    `선택한 좌석: ${selectedSeatsArray.join(', ')}` :
                    '선택한 좌석이 없습니다.';

                submitButton.disabled = selectedSeatsArray.length === 0;
            }
        });
    </script>

<?php endif; ?>

</body>
</html>