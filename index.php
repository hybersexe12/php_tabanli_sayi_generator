<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rastgele Sayı Üreteci</title>
    <link rel="stylesheet" href="style.css">
    <style>.game-promo2 {
    background-color: #3498db;
    padding: 30px;
    border-radius: 8px;
    margin-top: 30px;
    text-align: center;
    color: white;
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    position: relative;
    overflow: hidden;</style>
</head>
<body>
    <div class="container">
        <h1>Rastgele Sayı Üreteci</h1>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="min">Minimum Değer:</label>
                <input type="number" id="min" name="min" value="<?php echo isset($_POST['min']) ? $_POST['min'] : 1; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="max">Maximum Değer:</label>
                <input type="number" id="max" name="max" value="<?php echo isset($_POST['max']) ? $_POST['max'] : 100; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="count">Kaç Sayı:</label>
                <input type="number" id="count" name="count" value="<?php echo isset($_POST['count']) ? $_POST['count'] : 1; ?>" min="1" max="100" required>
            </div>
            
            <div class="form-group">
                <label for="unique">Benzersiz Sayılar:</label>
                <input type="checkbox" id="unique" name="unique" <?php echo (isset($_POST['unique']) && $_POST['unique'] == 'on') ? 'checked' : ''; ?>>
            </div>
            
            <button type="submit" name="generate">Sayı Üret</button>
        </form>
        
        <div class="result">
            <?php
            if (isset($_POST['generate'])) {
                $min = intval($_POST['min']);
                $max = intval($_POST['max']);
                $count = intval($_POST['count']);
                $unique = isset($_POST['unique']) && $_POST['unique'] == 'on';
                

                if ($min >= $max) {
                    echo "<p class='error'>Minimum değer, maximum değerden küçük olmalıdır!</p>";
                } elseif ($unique && ($max - $min + 1) < $count) {
                    echo "<p class='error'>Benzersiz $count sayı üretmek için aralık çok küçük!</p>";
                } else {
                    $numbers = array();
                    
                    if ($unique) {

                        $pool = range($min, $max);
                        shuffle($pool);
                        $numbers = array_slice($pool, 0, $count);
                    } else {

                        for ($i = 0; $i < $count; $i++) {
                            $numbers[] = rand($min, $max);
                        }
                    }
                    
                    echo "<h2>Sonuçlar:</h2>";
                    echo "<div class='numbers'>";
                    foreach ($numbers as $number) {
                        echo "<span class='number'>$number</span>";
                    }
                    echo "</div>";
                    

                    echo "<div class='stats'>";
                    echo "<p>En küçük: " . min($numbers) . "</p>";
                    echo "<p>En büyük: " . max($numbers) . "</p>";
                    echo "<p>Ortalama: " . round(array_sum($numbers) / count($numbers), 2) . "</p>";
                    echo "</div>";
                    

                    if (!isset($_SESSION)) {
                        session_start();
                    }
                    
                    if (!isset($_SESSION['history'])) {
                        $_SESSION['history'] = array();
                    }
                    

                    $_SESSION['history'][] = array(
                        'time' => date('H:i:s'),
                        'min' => $min,
                        'max' => $max,
                        'count' => $count,
                        'unique' => $unique,
                        'numbers' => $numbers
                    );
                    

                    if (count($_SESSION['history']) > 5) {
                        array_shift($_SESSION['history']);
                    }
                }
            }
            ?>
        </div>
        

        <div class="history">
            <h2>Geçmiş</h2>
            <?php
            if (!isset($_SESSION)) {
                session_start();
            }
            
            if (isset($_SESSION['history']) && !empty($_SESSION['history'])) {
                echo "<table>";
                echo "<tr><th>Zaman</th><th>Aralık</th><th>Sayı Adedi</th><th>Benzersiz</th><th>Sayılar</th></tr>";
                
                foreach (array_reverse($_SESSION['history']) as $item) {
                    echo "<tr>";
                    echo "<td>" . $item['time'] . "</td>";
                    echo "<td>" . $item['min'] . " - " . $item['max'] . "</td>";
                    echo "<td>" . $item['count'] . "</td>";
                    echo "<td>" . ($item['unique'] ? 'Evet' : 'Hayır') . "</td>";
                    echo "<td>" . implode(', ', $item['numbers']) . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>Henüz geçmiş yok.</p>";
            }
            ?>
        </div>
        

        <div class="game-promo2">
            <h2 style="color:white;">Sayı Tahmin Oyunu</h2>
            <p>Aklımdan bir sayı tuttum. Tahmin edebilir misin?</p><br>
            <a href="guess_game.php" class="game-button">Oyunu Oyna</a>
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
</body>
</html> 