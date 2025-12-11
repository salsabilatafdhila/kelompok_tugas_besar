<?php

// 1. KONFIGURASI API KEY
// JANGAN PERNAH menyimpan API KEY di kode front-end (index.html)! 
// Ambil dari variabel lingkungan atau file konfigurasi yang aman.
$apiKey = 'AIzaSyCbLuWeHY-swSy2L9g12exF4jEtvjk_OAo'; // <<< GANTI DENGAN KUNCI API ANDA

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['image_data'])) {
    http_response_code(400);
    die("Permintaan tidak valid.");
}

$base64Image = $_POST['image_data'];

// 2. PROMPT UNTUK GEMINI
// Kita menggunakan satu prompt multimodal yang meminta tiga hal sekaligus.
$prompt = "Analisis gambar bahan makanan ini. Berikan jawaban Anda dalam format berikut, menggunakan Markdown untuk keterbacaan:

### 🥦 Bahan Makanan yang Teridentifikasi
[Daftar bahan makanan yang ada di gambar]

---

### 🍳 Resep Otomatis
Buatkan satu resep sederhana dan cepat yang menggunakan bahan-bahan di atas. Sertakan:
1. Nama Resep
2. Bahan (Bahan dari gambar + bahan tambahan umum)
3. Langkah-Langkah

---

### ❄️ Saran Penyimpanan
Berikan saran penyimpanan terbaik (suhu, wadah) untuk bahan makanan yang Anda identifikasi untuk memaksimalkan kesegaran.
";

// 3. STRUKTUR PAYLOAD API (Permintaan Multimodal)
$payload = [
    'contents' => [
        [
            'parts' => [
                // Bagian 1: Data Gambar
                [
                    'inlineData' => [
                        'mimeType' => 'image/jpeg', // Asumsikan kita akan mengunggah JPEG
                        'data' => $base64Image 
                    ]
                ],
                // Bagian 2: Teks Prompt
                [
                    'text' => $prompt
                ]
            ]
        ]
    ]
];

$jsonPayload = json_encode($payload);

// 4. PANGGIL API MENGGUNAKAN cURL
$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonPayload)
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 5. PROSES RESPON
if ($httpCode === 200) {
    $responseData = json_decode($response, true);
    
    // Cek apakah ada respons yang valid
    $responseText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? "Error: Tidak dapat mengekstrak teks dari respons Gemini.";
    
    // Outputkan respons mentah (sudah diformat dengan Markdown dari prompt)
    echo $responseText;
    
} else {
    // Tangani error API
    http_response_code(500);
    echo "## ❌ Error saat Memanggil API\n";
    echo "Kode HTTP: " . $httpCode . "\n";
    echo "Detail Error: " . $response;
}

?>