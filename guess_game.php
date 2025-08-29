<?php

if (!isset($_SESSION)) {
    session_start();
}

$message = "";
$messageClass = "";
$gameStarted = false;
$gameOver = false;
$attempts = 0;
$maxAttempts = 10;
$hintMessage = "";
$secretNumber = 0;
$guessHistory = [];
$timeLeft = 60;
$difficulty = "normal";
$minRange = 1;
$maxRange = 100;
$score = 0;
$highScores = [];

if (file_exists('high_scores.json')) {
    $highScores = json_decode(file_get_contents('high_scores.json'), true);
}

if (isset($_POST['new_game'])) {
    $difficulty = isset($_POST['difficulty']) ? $_POST['difficulty'] : 'normal';
    
    switch ($difficulty) {
        case 'easy':
            $minRange = 1;
            $maxRange = 50;
            $maxAttempts = 15;
            $timeLeft = 90;
            break;
        case 'hard':
            $minRange = 1;
            $maxRange = 200;
            $maxAttempts = 8;
            $timeLeft = 45;
            break;
        case 'expert':
            $minRange = 1;
            $maxRange = 500;
            $maxAttempts = 6;
            $timeLeft = 30;
            break;
        default:
            $minRange = 1;
            $maxRange = 100;
            $maxAttempts = 10;
            $timeLeft = 60;
    }
    
    $_SESSION['secret_number'] = rand($minRange, $maxRange);
    $_SESSION['attempts'] = 0;
    $_SESSION['game_over'] = false;
    $_SESSION['guess_history'] = [];
    $_SESSION['start_time'] = time();
    $_SESSION['time_left'] = $timeLeft;
    $_SESSION['difficulty'] = $difficulty;
    $_SESSION['min_range'] = $minRange;
    $_SESSION['max_range'] = $maxRange;
    $_SESSION['max_attempts'] = $maxAttempts;
    
    $secretNumber = $_SESSION['secret_number'];
    $gameStarted = true;
    $message = "Yeni oyun başladı! $minRange ile $maxRange arasında bir sayı tuttum.";
    $messageClass = "info";
} 
else if (isset($_SESSION['secret_number'])) {
    $secretNumber = $_SESSION['secret_number'];
    $attempts = isset($_SESSION['attempts']) ? $_SESSION['attempts'] : 0;
    $gameOver = isset($_SESSION['game_over']) ? $_SESSION['game_over'] : false;
    $guessHistory = isset($_SESSION['guess_history']) ? $_SESSION['guess_history'] : [];
    $difficulty = isset($_SESSION['difficulty']) ? $_SESSION['difficulty'] : 'normal';
    $minRange = isset($_SESSION['min_range']) ? $_SESSION['min_range'] : 1;
    $maxRange = isset($_SESSION['max_range']) ? $_SESSION['max_range'] : 100;
    $maxAttempts = isset($_SESSION['max_attempts']) ? $_SESSION['max_attempts'] : 10;
    
    if (isset($_SESSION['start_time']) && isset($_SESSION['time_left'])) {
        $elapsedTime = time() - $_SESSION['start_time'];
        $timeLeft = max(0, $_SESSION['time_left'] - $elapsedTime);
        
        if ($timeLeft <= 0 && !$gameOver) {
            $gameOver = true;
            $_SESSION['game_over'] = true;
            $message = "Süre doldu! Doğru cevap: " . $secretNumber;
            $messageClass = "error";
        }
    }
    
    $gameStarted = true;
}

if (isset($_POST['guess']) && !$gameOver) {
    $guess = intval($_POST['guess']);
    $attempts = isset($_SESSION['attempts']) ? $_SESSION['attempts'] + 1 : 1;
    $_SESSION['attempts'] = $attempts;

    $guessHistory[] = [
        'number' => $guess,
        'result' => $guess < $secretNumber ? 'low' : ($guess > $secretNumber ? 'high' : 'correct')
    ];
    $_SESSION['guess_history'] = $guessHistory;
    
    if ($guess == $secretNumber) {
        $gameOver = true;
        $_SESSION['game_over'] = true;
        
        $elapsedTime = isset($_SESSION['start_time']) ? time() - $_SESSION['start_time'] : 0;
        $timeBonus = max(0, $_SESSION['time_left'] - $elapsedTime);
        $attemptsBonus = $_SESSION['max_attempts'] - $attempts;
        
        $difficultyMultiplier = 1;
        switch ($difficulty) {
            case 'easy': $difficultyMultiplier = 1; break;
            case 'normal': $difficultyMultiplier = 2; break;
            case 'hard': $difficultyMultiplier = 3; break;
            case 'expert': $difficultyMultiplier = 5; break;
        }
        
        $score = (100 + $timeBonus * 5 + $attemptsBonus * 10) * $difficultyMultiplier;
        
        $playerName = isset($_POST['player_name']) ? $_POST['player_name'] : 'Anonim';
        $highScores[] = [
            'name' => $playerName,
            'score' => $score,
            'difficulty' => $difficulty,
            'attempts' => $attempts,
            'time' => date('Y-m-d H:i:s')
        ];
        
        usort($highScores, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        $highScores = array_slice($highScores, 0, 10);
        
        file_put_contents('high_scores.json', json_encode($highScores));
        
        $message = "Tebrikler! $guess doğru tahmin. $attempts denemede buldunuz. Skorunuz: $score";
        $messageClass = "success";
    } elseif ($guess < $secretNumber) {
        $message = "Daha yüksek bir sayı deneyin.";
        $messageClass = "warning";
        
        $difference = $secretNumber - $guess;
        if ($difference <= 5) {
            $hintMessage = "Çok yaklaştınız!";
        } elseif ($difference <= 10) {
            $hintMessage = "Yaklaşıyorsunuz!";
        } else {
            $hintMessage = "Daha çok yol var.";
        }
    } else {
        $message = "Daha düşük bir sayı deneyin.";
        $messageClass = "warning";
        
        $difference = $guess - $secretNumber;
        if ($difference <= 5) {
            $hintMessage = "Çok yaklaştınız!";
        } elseif ($difference <= 10) {
            $hintMessage = "Yaklaşıyorsunuz!";
        } else {
            $hintMessage = "Daha çok yol var.";
        }
    }
    
    if ($attempts >= $maxAttempts && !$gameOver) {
        $gameOver = true;
        $_SESSION['game_over'] = true;
        $message = "Üzgünüm, $maxAttempts deneme hakkınız doldu. Doğru sayı: $secretNumber idi.";
        $messageClass = "error";
    }
}

if (isset($_POST['hint']) && !$gameOver) {
    $attempts = isset($_SESSION['attempts']) ? $_SESSION['attempts'] + 1 : 1;
    $_SESSION['attempts'] = $attempts;
    
    $range = floor(($maxRange - $minRange) / 10);
    $lowerBound = max($minRange, $secretNumber - $range);
    $upperBound = min($maxRange, $secretNumber + $range);
    
    $hintMessage = "İpucu: Sayı $lowerBound ile $upperBound arasında olabilir.";

    $guessHistory[] = [
        'number' => '?',
        'result' => 'hint',
        'message' => $hintMessage
    ];
    $_SESSION['guess_history'] = $guessHistory;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sayı Tahmin Oyunu</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .game-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin-top: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .game-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .game-title {
            font-size: 2.5em;
            color: #2c3e50;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .game-subtitle {
            font-size: 1.2em;
            color: #7f8c8d;
        }
        
        .game-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-item {
            text-align: center;
            flex: 1;
        }
        
        .info-label {
            font-weight: bold;
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .info-value {
            font-size: 1.2em;
            color: #2c3e50;
            font-weight: bold;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .hint {
            font-style: italic;
            text-align: center;
            margin: 10px 0;
            color: #6c757d;
        }
        
        .game-form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .input-group {
            width: 100%;
            margin-bottom: 15px;
        }
        
        .input-group input {
            flex: 1;
            padding: 12px;
            font-size: 1.2em;
            border: 2px solid #ddd;
            border-radius: 8px 0 0 8px;
        }
        
        .input-group button {
            padding: 12px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 0 8px 8px 0;
            cursor: pointer;
            font-size: 1.2em;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 15px;
        }
        
        .action-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .hint-btn {
            background-color: #f39c12;
            color: white;
        }
        
        .new-game-btn {
            background-color: #2ecc71;
            color: white;
        }
        
        .history-container {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        
        .history-title {
            text-align: center;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        
        .history-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }
        
        .history-item {
            width: 60px;
            height: 60px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            font-weight: bold;
            font-size: 1.2em;
            position: relative;
        }
        
        .history-item.low {
            background-color: #f39c12;
            color: white;
        }
        
        .history-item.high {
            background-color: #9b59b6;
            color: white;
        }
        
        .history-item.correct {
            background-color: #2ecc71;
            color: white;
            animation: pulse 1s infinite;
        }
        
        .history-item.hint {
            background-color: #3498db;
            color: white;
            font-size: 1.5em;
        }
        
        .history-item::after {
            content: "";
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 8px solid transparent;
            border-right: 8px solid transparent;
        }
        
        .history-item.low::after {
            border-bottom: 8px solid #f39c12;
        }
        
        .history-item.high::after {
            border-top: 8px solid #9b59b6;
        }
        
        .timer-container {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .timer-bar {
            height: 10px;
            background-color: #ecf0f1;
            border-radius: 5px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .timer-progress {
            height: 100%;
            background-color: #3498db;
            border-radius: 5px;
            transition: width 1s linear;
        }
        
        .timer-low {
            background-color: #e74c3c;
        }
        
        .difficulty-selector {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }
        
        .difficulty-btn {
            padding: 10px 15px;
            border: 2px solid #3498db;
            background-color: white;
            color: #3498db;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .difficulty-btn:hover, .difficulty-btn.active {
            background-color: #3498db;
            color: white;
        }
        
        .high-scores {
            margin-top: 30px;
        }
        
        .high-scores h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .score-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .score-table th, .score-table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        
        .score-table th {
            background-color: #3498db;
            color: white;
        }
        
        .score-table tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .score-table tr:first-child td {
            font-weight: bold;
            background-color: #f1c40f;
            color: #2c3e50;
        }
        
        .player-form {
            margin-top: 20px;
            text-align: center;
        }
        
        .player-form input {
            padding: 10px;
            width: 100%;
            max-width: 300px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .game-promo {
    max-width: 800px;
    background-color: #3498db;
    padding: 30px;
    border-radius: 8px;
    margin-top: 30px;
    text-align: center;
    color: white;
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    position: relative;
    overflow: hidden;
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: #f39c12;
            opacity: 0.7;
            animation: confetti-fall 3s linear infinite;
        }
        
        @keyframes confetti-fall {
            0% { transform: translateY(-100px) rotate(0deg); opacity: 1; }
            100% { transform: translateY(600px) rotate(360deg); opacity: 0; }
        }
    </style>
</head>
<body>
    <div class="game-container">
        <?php if ($gameOver && $messageClass == "success"): ?>
            <div id="confetti-container"></div>
        <?php endif; ?>
        
        <div class="game-header">
            <h1 class="game-title">Sayı Tahmin Oyunu</h1>
            <p class="game-subtitle">Aklımdaki sayıyı tahmin edebilir misin?</p>
        </div>
        
        <?php if ($gameStarted): ?>
            <div class="game-info">
                <div class="info-item">
                    <div class="info-label">Zorluk</div>
                    <div class="info-value"><?php 
                        switch($difficulty) {
                            case 'easy': echo 'Kolay'; break;
                            case 'normal': echo 'Normal'; break;
                            case 'hard': echo 'Zor'; break;
                            case 'expert': echo 'Uzman'; break;
                        }
                    ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Aralık</div>
                    <div class="info-value"><?php echo $minRange . ' - ' . $maxRange; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Deneme</div>
                    <div class="info-value"><?php echo $attempts . ' / ' . $maxAttempts; ?></div>
                </div>
            </div>
            
            <?php if (!$gameOver): ?>
                <div class="timer-container">
                    <div class="info-label">Kalan Süre: <span id="time-left"><?php echo $timeLeft; ?></span> saniye</div>
                    <div class="timer-bar">
                        <div id="timer-progress" class="timer-progress" style="width: <?php echo ($timeLeft / $_SESSION['time_left']) * 100; ?>%"></div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageClass; ?>"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($hintMessage)): ?>
            <div class="hint"><?php echo $hintMessage; ?></div>
        <?php endif; ?>
        
        <?php if (!$gameStarted || $gameOver): ?>
            <form method="post" action="" class="game-form">
                <h3>Yeni Oyun Başlat</h3>
                
                <div class="difficulty-selector">
                    <button type="button" class="difficulty-btn" onclick="selectDifficulty('easy')">Kolay</button>
                    <button type="button" class="difficulty-btn active" onclick="selectDifficulty('normal')">Normal</button>
                    <button type="button" class="difficulty-btn" onclick="selectDifficulty('hard')">Zor</button>
                    <button type="button" class="difficulty-btn" onclick="selectDifficulty('expert')">Uzman</button>
                </div>
                
                <input type="hidden" id="difficulty" name="difficulty" value="normal">
                
                <?php if ($gameOver && $messageClass == "success"): ?>
                    <div class="player-form">
                        <h3>Tebrikler! Skorunuz: <?php echo $score; ?></h3>
                        <p>Adınızı girerek yüksek skor tablosuna kaydolun:</p>
                        <input type="text" name="player_name" placeholder="Adınız" required>
                    </div>
                <?php endif; ?>
                
                <button type="submit" name="new_game" class="new-game-btn">Yeni Oyun</button>
            </form>
        <?php else: ?>
            <form method="post" action="" class="game-form">
                <div class="input-group">
                    <input type="number" name="guess" min="<?php echo $minRange; ?>" max="<?php echo $maxRange; ?>" placeholder="Tahmininizi girin..." required autofocus>
                    <button type="submit">Tahmin Et</button>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" name="hint" class="hint-btn">İpucu Al</button>
                    <button type="submit" name="new_game" class="new-game-btn">Yeni Oyun</button>
                </div>
            </form>
        <?php endif; ?>
        
        <?php if (!empty($guessHistory)): ?>
            <div class="history-container">
                <h3 class="history-title">Tahmin Geçmişi</h3>
                <div class="history-list">
                    <?php foreach ($guessHistory as $index => $guess): ?>
                        <div class="history-item <?php echo $guess['result']; ?>" title="<?php echo isset($guess['message']) ? $guess['message'] : ''; ?>">
                            <?php echo $guess['number']; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="high-scores">
            <h2>Yüksek Skorlar</h2>
            <?php if (!empty($highScores)): ?>
                <table class="score-table">
                    <tr>
                        <th>Sıra</th>
                        <th>İsim</th>
                        <th>Skor</th>
                        <th>Zorluk</th>
                        <th>Deneme</th>
                        <th>Tarih</th>
                    </tr>
                    <?php foreach ($highScores as $index => $score): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($score['name']); ?></td>
                            <td><?php echo $score['score']; ?></td>
                            <td><?php 
                                switch($score['difficulty']) {
                                    case 'easy': echo 'Kolay'; break;
                                    case 'normal': echo 'Normal'; break;
                                    case 'hard': echo 'Zor'; break;
                                    case 'expert': echo 'Uzman'; break;
                                    default: echo $score['difficulty'];
                                }
                            ?></td>
                            <td><?php echo $score['attempts']; ?></td>
                            <td><?php echo $score['time']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p style="text-align: center;">Henüz yüksek skor bulunmuyor.</p>
            <?php endif; ?>
        </div>
        <div class="game-promo">
            <p>Anasayfa'ya dönmek istermisin?</p>
            <a href="index.php" class="game-button">Anasayfa'ya Git</a>
        </div>
    </div>
    </div>
    
    <footer class="container" style="
  max-width: 800px;
  border-radius:15px;
  height: 60px;
  background: linear-gradient(to right, #e0f7ff, #ffffff);
  padding: 15px 0;
  text-align: center;
  border-top: 1px solid #cceeff;
  box-shadow: 0 -4px 10px rgba(0, 85, 170, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
">
  <p id="typing" style="
    display: inline-block;
    font-size: 18px;
    font-weight: 500;
    color: #007acc;
    white-space: nowrap;
    overflow: hidden;
    border-right: 2px solid #007acc;
    min-width: 200px;
    animation: blink 0.8s step-start infinite;
  "></p>
</footer>

<script>
  (function(){
    const text = "powered by Hüseyin ÜNLÜER ❤️ ";
    const target = document.getElementById("typing");
    let i = 0;

    function typeOnce() {
      if (i < text.length) {
        target.textContent += text.charAt(i);
        i++;
        setTimeout(typeOnce, 60);
      }
    }
    typeOnce();
  })();
</script>

<style>
  @keyframes blink {
    0%, 50% { border-color: #007acc; }
    51%, 100% { border-color: transparent; }
  }
</style>
    
                    <script>
        function selectDifficulty(level) {
            document.getElementById('difficulty').value = level;
            
            const buttons = document.querySelectorAll('.difficulty-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            event.target.classList.add('active');
        }
        
        <?php if (!$gameOver && $gameStarted): ?>
        let timeLeft = <?php echo $timeLeft; ?>;
        const timerElement = document.getElementById('time-left');
        const timerProgress = document.getElementById('timer-progress');
        const totalTime = <?php echo $_SESSION['time_left']; ?>;
        
        const timer = setInterval(function() {
            timeLeft--;
            
            if (timeLeft <= 0) {
                clearInterval(timer);
                window.location.reload();
            }
            
            timerElement.textContent = timeLeft;
            
            const percentage = (timeLeft / totalTime) * 100;
            timerProgress.style.width = percentage + '%';
            
            if (timeLeft <= 10) {
                timerProgress.classList.add('timer-low');
            }
        }, 1000);
        <?php endif; ?>
        
        <?php if ($gameOver && $messageClass == "success"): ?>
        function createConfetti() {
            const container = document.getElementById('confetti-container');
            const colors = ['#f39c12', '#3498db', '#2ecc71', '#e74c3c', '#9b59b6', '#1abc9c'];
            
            for (let i = 0; i < 100; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.width = Math.random() * 10 + 5 + 'px';
                confetti.style.height = Math.random() * 10 + 5 + 'px';
                confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
                confetti.style.animationDelay = Math.random() * 5 + 's';
                
                container.appendChild(confetti);
            }
        }
        
        createConfetti();
        <?php endif; ?>
    </script>
</body>
</html> <?php

if (!isset($_SESSION)) {
    session_start();
}

$message = "";
$messageClass = "";
$gameStarted = false;
$gameOver = false;
$attempts = 0;
$maxAttempts = 10;
$hintMessage = "";
$secretNumber = 0;
$guessHistory = [];
$timeLeft = 60;
$difficulty = "normal";
$minRange = 1;
$maxRange = 100;
$score = 0;
$highScores = [];

if (file_exists('high_scores.json')) {
    $highScores = json_decode(file_get_contents('high_scores.json'), true);
}

if (isset($_POST['new_game'])) {
    $difficulty = isset($_POST['difficulty']) ? $_POST['difficulty'] : 'normal';
    
    switch ($difficulty) {
        case 'easy':
            $minRange = 1;
            $maxRange = 50;
            $maxAttempts = 15;
            $timeLeft = 90;
            break;
        case 'hard':
            $minRange = 1;
            $maxRange = 200;
            $maxAttempts = 8;
            $timeLeft = 45;
            break;
        case 'expert':
            $minRange = 1;
            $maxRange = 500;
            $maxAttempts = 6;
            $timeLeft = 30;
            break;
        default:
            $minRange = 1;
            $maxRange = 100;
            $maxAttempts = 10;
            $timeLeft = 60;
    }
    
    $_SESSION['secret_number'] = rand($minRange, $maxRange);
    $_SESSION['attempts'] = 0;
    $_SESSION['game_over'] = false;
    $_SESSION['guess_history'] = [];
    $_SESSION['start_time'] = time();
    $_SESSION['time_left'] = $timeLeft;
    $_SESSION['difficulty'] = $difficulty;
    $_SESSION['min_range'] = $minRange;
    $_SESSION['max_range'] = $maxRange;
    $_SESSION['max_attempts'] = $maxAttempts;
    
    $secretNumber = $_SESSION['secret_number'];
    $gameStarted = true;
    $message = "Yeni oyun başladı! $minRange ile $maxRange arasında bir sayı tuttum.";
    $messageClass = "info";
} 
else if (isset($_SESSION['secret_number'])) {
    $secretNumber = $_SESSION['secret_number'];
    $attempts = isset($_SESSION['attempts']) ? $_SESSION['attempts'] : 0;
    $gameOver = isset($_SESSION['game_over']) ? $_SESSION['game_over'] : false;
    $guessHistory = isset($_SESSION['guess_history']) ? $_SESSION['guess_history'] : [];
    $difficulty = isset($_SESSION['difficulty']) ? $_SESSION['difficulty'] : 'normal';
    $minRange = isset($_SESSION['min_range']) ? $_SESSION['min_range'] : 1;
    $maxRange = isset($_SESSION['max_range']) ? $_SESSION['max_range'] : 100;
    $maxAttempts = isset($_SESSION['max_attempts']) ? $_SESSION['max_attempts'] : 10;
    
    if (isset($_SESSION['start_time']) && isset($_SESSION['time_left'])) {
        $elapsedTime = time() - $_SESSION['start_time'];
        $timeLeft = max(0, $_SESSION['time_left'] - $elapsedTime);
        
        if ($timeLeft <= 0 && !$gameOver) {
            $gameOver = true;
            $_SESSION['game_over'] = true;
            $message = "Süre doldu! Doğru cevap: " . $secretNumber;
            $messageClass = "error";
        }
    }
    
    $gameStarted = true;
}

if (isset($_POST['guess']) && !$gameOver) {
    $guess = intval($_POST['guess']);
    $attempts = isset($_SESSION['attempts']) ? $_SESSION['attempts'] + 1 : 1;
    $_SESSION['attempts'] = $attempts;

    $guessHistory[] = [
        'number' => $guess,
        'result' => $guess < $secretNumber ? 'low' : ($guess > $secretNumber ? 'high' : 'correct')
    ];
    $_SESSION['guess_history'] = $guessHistory;
    
    if ($guess == $secretNumber) {
        $gameOver = true;
        $_SESSION['game_over'] = true;
        
        $elapsedTime = isset($_SESSION['start_time']) ? time() - $_SESSION['start_time'] : 0;
        $timeBonus = max(0, $_SESSION['time_left'] - $elapsedTime);
        $attemptsBonus = $_SESSION['max_attempts'] - $attempts;
        
        $difficultyMultiplier = 1;
        switch ($difficulty) {
            case 'easy': $difficultyMultiplier = 1; break;
            case 'normal': $difficultyMultiplier = 2; break;
            case 'hard': $difficultyMultiplier = 3; break;
            case 'expert': $difficultyMultiplier = 5; break;
        }
        
        $score = (100 + $timeBonus * 5 + $attemptsBonus * 10) * $difficultyMultiplier;
        
        $playerName = isset($_POST['player_name']) ? $_POST['player_name'] : 'Anonim';
        $highScores[] = [
            'name' => $playerName,
            'score' => $score,
            'difficulty' => $difficulty,
            'attempts' => $attempts,
            'time' => date('Y-m-d H:i:s')
        ];
        
        usort($highScores, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        $highScores = array_slice($highScores, 0, 10);
        
        file_put_contents('high_scores.json', json_encode($highScores));
        
        $message = "Tebrikler! $guess doğru tahmin. $attempts denemede buldunuz. Skorunuz: $score";
        $messageClass = "success";
    } elseif ($guess < $secretNumber) {
        $message = "Daha yüksek bir sayı deneyin.";
        $messageClass = "warning";
        
        $difference = $secretNumber - $guess;
        if ($difference <= 5) {
            $hintMessage = "Çok yaklaştınız!";
        } elseif ($difference <= 10) {
            $hintMessage = "Yaklaşıyorsunuz!";
        } else {
            $hintMessage = "Daha çok yol var.";
        }
    } else {
        $message = "Daha düşük bir sayı deneyin.";
        $messageClass = "warning";
        
        $difference = $guess - $secretNumber;
        if ($difference <= 5) {
            $hintMessage = "Çok yaklaştınız!";
        } elseif ($difference <= 10) {
            $hintMessage = "Yaklaşıyorsunuz!";
        } else {
            $hintMessage = "Daha çok yol var.";
        }
    }
    
    if ($attempts >= $maxAttempts && !$gameOver) {
        $gameOver = true;
        $_SESSION['game_over'] = true;
        $message = "Üzgünüm, $maxAttempts deneme hakkınız doldu. Doğru sayı: $secretNumber idi.";
        $messageClass = "error";
    }
}

if (isset($_POST['hint']) && !$gameOver) {
    $attempts = isset($_SESSION['attempts']) ? $_SESSION['attempts'] + 1 : 1;
    $_SESSION['attempts'] = $attempts;
    
    $range = floor(($maxRange - $minRange) / 10);
    $lowerBound = max($minRange, $secretNumber - $range);
    $upperBound = min($maxRange, $secretNumber + $range);
    
    $hintMessage = "İpucu: Sayı $lowerBound ile $upperBound arasında olabilir.";

    $guessHistory[] = [
        'number' => '?',
        'result' => 'hint',
        'message' => $hintMessage
    ];
    $_SESSION['guess_history'] = $guessHistory;
}
?>

