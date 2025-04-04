<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin ThingSpeak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container mt-5">
        <h2 class="text-center">Thông tin từ ThingSpeak</h2>

        <!-- Form nhập channel_id và api_key -->
        <form method="post" action="">
            <div class="mb-3">
                <label for="channel_id" class="form-label">Channel ID</label>

                <input type="text" class="form-control" id="channel_id" name="channel_id"
                    value="<?php echo isset($_COOKIE['channel_id']) ? $_COOKIE['channel_id'] : ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="api_key" class="form-label">API Key</label>

                <input type="text" class="form-control" id="api_key" name="api_key"
                    value="<?php echo isset($_COOKIE['api_key']) ? $_COOKIE['api_key'] : ''; ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Lấy Dữ Liệu</button>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $channel_id = $_POST['channel_id'];
            $api_key = $_POST['api_key'];


            // Lưu Channel ID và API Key vào cookie trong 30 ngày
            setcookie('channel_id', $channel_id, time() + (86400 * 30), "/");
            setcookie('api_key', $api_key, time() + (86400 * 30), "/");

            $feed_url = "https://api.thingspeak.com/channels/$channel_id/feeds.json?api_key=$api_key&results=1";
            $response = file_get_contents($feed_url);

            if ($response === FALSE) {
                echo "<p class='text-danger'>Có lỗi xảy ra khi lấy dữ liệu từ ThingSpeak.</p>";
            } else {
                $data = json_decode($response, true);

                if (isset($data['feeds'][0])) {
                    $latest_feed = $data['feeds'][0];
                    $latest_temperature = isset($latest_feed['field1']) ? $latest_feed['field1'] : "N/A";
                    $latest_humidity = isset($latest_feed['field2']) ? $latest_feed['field2'] : "N/A";
                    $latest_gas = isset($latest_feed['field3']) ? $latest_feed['field3'] : "N/A";
                    $latest_timestamp = $latest_feed['created_at'];

                    echo "<div class='card mb-3'>
                            <div class='card-body'>
                                <h5 class='card-title'>Dữ liệu hiện tại lúc " . $latest_timestamp . "</h5>
                                <p><i class='fas fa-thermometer-half'></i> <strong>Nhiệt độ:</strong> $latest_temperature °C</p>
                                <p><i class='fas fa-tint'></i> <strong>Độ ẩm:</strong> $latest_humidity %</p>
                                <p><i class='fas fa-biohazard'></i> <strong>Khí gas:</strong> $latest_gas ppm</p>
                            </div>
                        </div>";
                } else {
                    echo "<p>Không có dữ liệu hiện tại.</p>";
                }


                $feed_url_all = "https://api.thingspeak.com/channels/$channel_id/feeds.json?api_key=$api_key&results=200";
                $response_all = file_get_contents($feed_url_all);

                if ($response_all === FALSE) {
                    echo "<p class='text-danger'>Có lỗi xảy ra khi lấy tất cả dữ liệu.</p>";
                } else {
                    $data_all = json_decode($response_all, true);

                    $temperature_data = [];
                    $humidity_data = [];
                    $gas_data = [];
                    $labels = [];

                    if (isset($data_all['feeds'])) {
                        foreach ($data_all['feeds'] as $feed) {
                            $temperature_data[] = isset($feed['field1']) ? $feed['field1'] : "N/A";
                            $humidity_data[] = isset($feed['field2']) ? $feed['field2'] : "N/A";
                            $gas_data[] = isset($feed['field3']) ? $feed['field3'] : "N/A";
                            $labels[] = $feed['created_at'];
                        }
                    }
                }
            }
        }
        ?>


        <h3 class="text-center mb-4">Biểu đồ Nhiệt độ, Độ ẩm và Khí gas</h3>
        <canvas id="chart"></canvas>
    </div>

    <script>
    <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($data_all['feeds'])) { ?>
    var ctx = document.getElementById('chart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'line',
        data: {

            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Nhiệt độ (°C)',

                data: <?php echo json_encode($temperature_data); ?>,
                borderColor: 'rgb(255, 99, 132)',
                fill: false
            }, {
                label: 'Độ ẩm (%)',

                data: <?php echo json_encode($humidity_data); ?>,
                borderColor: 'rgb(54, 162, 235)',
                fill: false
            }, {
                label: 'Khí gas (ppm)',
                data: <?php echo json_encode($gas_data); ?>,
                borderColor: 'rgb(75, 192, 192)',
                fill: false
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    <?php } ?>
    </script>
</body>

</html>