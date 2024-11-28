<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db_connect.php';

try {
    $db = DatabaseConnection::getInstance();

    // 스케줄 정보 가져오기
    function getScheduleInfo($scheduleId) {
        global $db;
        return $db->executeQuery(
            "SELECT s.schedule_id, 
                    s.start_time,
                    s.end_time,
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
    }

    // 좌석 정보 가져오기 - 행별로 그룹화
    function getSeatsGroupedByRow($theaterId, $scheduleId) {
        global $db;

        // 디버깅: SCHEDULE_SEATS 테이블 확인
        $checkScheduleSeats = $db->executeQuery(
            "SELECT * FROM SCHEDULE_SEATS WHERE SCHEDULE_ID = :schedule_id",
            ['schedule_id' => $scheduleId]
        );
        echo "<pre>Schedule Seats Status: ";
        print_r($checkScheduleSeats);
        echo "</pre>";

        $seats = $db->executeQuery(
            "SELECT TS.SEAT_ID, 
                TS.SEAT_ROW, 
                TS.SEAT_NUMBER,
                NVL(SS.STATUS, 'AVAILABLE') as STATUS
         FROM THEATER_SEATS TS
         LEFT OUTER JOIN SCHEDULE_SEATS SS 
             ON TS.SEAT_ID = SS.SEAT_ID 
             AND SS.SCHEDULE_ID = :schedule_id
         WHERE TS.THEATER_ID = :theater_id
         ORDER BY TS.SEAT_ROW, TS.SEAT_NUMBER",
            [
                'theater_id' => $theaterId,
                'schedule_id' => $scheduleId
            ]
        );

        return $seats;  // 그룹화하지 않고 정렬된 순서 그대로 반환
    }

    $scheduleId = $_GET['schedule'] ?? '';
    if (empty($scheduleId)) {
        throw new Exception('상영 일정 정보가 없습니다.');
    }

    $scheduleInfo = getScheduleInfo($scheduleId);
    if (empty($scheduleInfo)) {
        throw new Exception('잘못된 상영 일정입니다.');
    }

    $scheduleInfo = $scheduleInfo[0];
    $theaterId = $scheduleInfo['THEATER_ID'];

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
    <link href="https://unpkg.com/heroicons@2.0.18/outline" rel="stylesheet">
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
        }
        .seat.vip {
            background-color: #E9D5FF;
        }
        .seat.standard {
            background-color: #DBEAFE;
        }
        .seat.disabled {
            background-color: #FEF3C7;
        }
        .seat.occupied {
            background-color: #D1D5DB;
            cursor: not-allowed;
        }
        .seat.selected {
            background-color: #34D399 !important;
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

<!-- 앱바 추가 -->
<div class="bg-white shadow-md">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
            <!-- 뒤로가기 버튼 -->
            <div class="flex items-center">
                <a href="index.php" class="text-gray-700 hover:text-gray-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
            </div>

            <!-- 로고와 이름 -->
            <div class="flex items-center">
<!--                <img src="/path/to/your/logo.png" alt="Logo" class="h-8 w-8 mr-2">-->
                <span class="text-xl font-semibold text-gray-900">무비핑</span>
            </div>

            <!-- 우측 여백을 위한 빈 div (균형을 맞추기 위함) -->
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
                <p><?= htmlspecialchars($scheduleInfo['THEATER_NAME']) ?></p>
                <p><?= date('Y년 m월 d일 H:i', strtotime($scheduleInfo['START_TIME'])) ?> ~
                    <?= date('H:i', strtotime($scheduleInfo['END_TIME'])) ?></p>
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
                    $seats = getSeatsGroupedByRow($scheduleId, $theaterId);

                    foreach ($seats as $seat) {
                        // 새로운 행이 시작될 때
                        if ($currentRow !== $seat['SEAT_ROW']) {
                            if ($currentRow !== '') {
                                echo "</div>"; // 이전 행 닫기
                            }
                            $currentRow = $seat['SEAT_ROW'];
                            echo "<div class='seat-row'>";
                            echo "<div class='row-label'>{$seat['SEAT_ROW']}</div>";
                            echo "<div class='seats-container'>";
                        }

                        // 좌석 타입에 따른 클래스 설정
                        $seatClass = 'seat ';


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
                    // 마지막 행 닫기
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
                </div><!-- 선택한 좌석 정보 -->
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

            // 좌석 클릭 이벤트 처리 (기존 코드와 동일)
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

            // 폼 제출 이벤트 처리
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (selectedSeats.size === 0) {
                    alert('좌석을 선택해주세요.');
                    return;
                }

                const formData = new FormData(form);

                // 선택된 좌석 정보 추가
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
                // 기존 updateSelectedSeatsInfo 함수 내용
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

