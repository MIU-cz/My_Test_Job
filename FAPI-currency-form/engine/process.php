<?php
header('Content-Type: application/json');

// Получение данных из запроса
$data = json_decode(file_get_contents('php://input'), true);

// Проверка необходимых полей
if (!isset($data['name'], $data['email'], $data['phone'], $data['product'], $data['price'], $data['quantity'], $data['totalPrice'])) {
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// Конвертация валюты
$currency = 'USD'; // Или другая нужная валюта
$exchangeRate = 1; // По умолчанию, если не удается получить курс

// Путь к файлу с курсами валют
$filename = 'denni_kurz_24.06.2024.txt';

// Проверяем существование файла
if (!file_exists($filename)) {
    echo json_encode(['error' => 'Currency rate file not found']);
    exit;
}

// Чтение содержимого файла в массив строк
$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Поиск курса валюты
$found = false;
foreach ($lines as $line) {
    // Проверяем, что строка начинается с "USA|"
    if (strpos($line, 'USA|') === 0) {
        // Разбиваем строку на части по разделителю "|"
        $parts = explode('|', $line);
        
        // Проверяем, что у нас есть достаточно частей и валюта совпадает с искомой
        if (count($parts) >= 5 && trim($parts[3]) === $currency) {
            // Извлекаем курс из строки и преобразуем в число
            $exchangeRate = floatval(str_replace(',', '.', $parts[2]));
            $found = true;
            break;
        }
    }
}

// Если курс не был найден, возвращаем ошибку
if (!$found) {
    echo json_encode(['error' => 'Exchange rate not found for ' . $currency]);
    exit;
}

// Расчет общей цены с НДС
$totalPrice = $data['totalPrice'];
$vat = $totalPrice * 0.21; // НДС 21%
$totalPriceWithVat = $totalPrice + $vat;
$totalPriceConverted = $totalPriceWithVat / $exchangeRate;

// Возвращаем данные клиенту
echo json_encode([
    'name' => htmlspecialchars($data['name']),
    'email' => htmlspecialchars($data['email']),
    'phone' => htmlspecialchars($data['phone']),
    'product' => htmlspecialchars($data['product']),
    'price' => (float)$data['price'],
    'quantity' => (int)$data['quantity'],
    'totalPrice' => round($totalPrice, 2),
    'totalPriceWithVat' => round($totalPriceWithVat, 2),
    'totalPriceConverted' => round($totalPriceConverted, 2),
    'currency' => $currency,
    'vat' => round($vat, 2)
]);
?>
