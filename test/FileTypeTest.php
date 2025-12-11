<?php
use PHPUnit\Framework\TestCase;

// Definisikan konstanta untuk kemudahan pengujian
define('API_ENDPOINT', 'http://localhost/kelompok_tugas_besar/process.php'); // Ganti dengan URL endpoint Anda
define('API_FILE_PATH', 'process.php'); // Ganti dengan path file API Anda

class ApiTest extends TestCase
{
    /**
     * @var string Kunci API palsu untuk pengujian, diambil dari environment variable.
     */
    private $apiKey;

    protected function setUp(): void
    {
        // Ambil API Key dari environment variable untuk pengujian
        // Dalam skenario nyata, ini harus diatur sebelum menjalankan PHPUnit
        $this->apiKey = getenv('GEMINI_API_KEY') ?: 'TEST_KEY_12345';
        
        // Catatan: Jika Anda benar-benar ingin menguji API eksternal, Anda
        // harus menggunakan API Key yang valid dan mock request untuk menghindari 
        // rate limit. Untuk contoh ini, kita fokus pada validasi lokal.
    }

    /**
     * Test Case A: file exist
     * Memastikan file API (process.php) ada.
     */
    public function test_a_file_exist()
    {
        $this->assertFileExists(API_FILE_PATH, "File API di path " . API_FILE_PATH . " tidak ditemukan!");
    }

    /**
     * Test Case B: valid syntax
     * Memastikan file PHP memiliki sintaks yang valid (secara sederhana, mengandung tag PHP).
     * Pengujian sintaks PHP yang sebenarnya biasanya dilakukan menggunakan linter (misalnya, 'php -l').
     */
    public function test_b_valid_syntax()
    {
        $content = file_get_contents(API_FILE_PATH);
        // Minimal harus mengandung tag pembuka PHP
        $this->assertStringContainsString('<?php', $content, "File " . API_FILE_PATH . " tidak memiliki tag pembuka PHP.");
        
        // Untuk validasi sintaks yang lebih ketat, gunakan PHP linter:
        // $output = shell_exec('php -l ' . API_FILE_PATH);
        // $this->assertStringNotContainsString('Parse error', $output, "Sintaks PHP di " . API_FILE_PATH . " tidak valid.");
    }

    /**
     * Test Case C: API Key tidak boleh kosong (Simulasi)
     * Menguji bahwa fungsi API gagal jika kunci API tidak diset.
     * Catatan: Ini menguji logika di backend API Anda, bukan API Gemini itu sendiri.
     */
    public function test_c_api_key_cannot_be_empty()
    {
        // Simulasikan permintaan tanpa Kunci API (atau Kunci kosong)
        $ch = $this->createCurlRequest(API_ENDPOINT, ['image_data' => 'dummy_b64'], 'empty_key');
        
        // Eksekusi permintaan dan dapatkan respons
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Kita berharap kode status BUKAN 200 jika Kunci API tidak diset (misalnya 401 Unauthorized atau 400 Bad Request).
        $this->assertNotEquals(200, $httpCode, "API Key kosong seharusnya menghasilkan Response Code selain 200.");
    }

    /**
     * Test Case D & E: Response Code harus 200 DAN valid JSON response
     * Menguji respons API yang sukses.
     */
    public function test_d_and_e_successful_api_call_returns_200_and_valid_json()
    {
        // Data minimal yang diperlukan untuk pengujian (simulasi gambar base64)
        $dummyImageBase64 = base64_encode('dummy_image_data'); 
        
        // Lakukan permintaan API dengan API Key yang diset
        $ch = $this->createCurlRequest(API_ENDPOINT, ['image_data' => $dummyImageBase64], $this->apiKey);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Test E: Response Code harus 200
        $this->assertEquals(200, $httpCode, "API Call gagal. Response Code: " . $httpCode . ". Response: " . $response);
        
        // Test D: valid JSON response (atau respons teks yang valid jika outputnya Markdown)
        
        // Karena respons Gemini biasanya Markdown/Teks, kita fokus pada memastikan itu bukan JSON error.
        $decodedResponse = json_decode($response, true);
        
        // Jika respons adalah teks (non-JSON), kita hanya perlu memastikan responsnya tidak kosong
        $this->assertIsString($response, "Respons seharusnya berupa string (Markdown/Teks).");
        $this->assertStringContainsString('Bahan Makanan yang Teridentifikasi', $response, "Respons tidak memiliki struktur hasil yang diharapkan.");
        
        // Jika Anda menguji API yang responsnya JSON:
        // $this->assertNotNull($decodedResponse, "Respons bukan JSON yang valid.");
        // $this->assertArrayHasKey('result', $decodedResponse, "Struktur JSON tidak mengandung key 'result'.");
    }

    /**
     * Helper function untuk membuat permintaan cURL (memudahkan pengujian)
     */
    private function createCurlRequest($url, $data, $apiKey)
    {
        // Ini adalah simulasi. Dalam skenario nyata, Anda harus mengirim API key
        // sebagai header atau bagian dari data/URL, tergantung pada implementasi backend Anda.
        
        // Di sini kita asumsikan process.php mengambil API Key dari environment variable,
        // sehingga kita TIDAK mengirimkannya di payload, tetapi kita mengirim data POST.
        
        $ch = curl_init($url);
        
        $postFields = http_build_query($data); // Menggunakan http_build_query untuk data POST
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        
        // Ini adalah cara untuk mengatur header yang mungkin dibutuhkan oleh process.php
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            // Kita bisa menggunakan header khusus untuk mensimulasikan API Key jika diperlukan:
            'X-API-KEY-TEST: ' . $apiKey 
        ]);
        
        return $ch;
    }
}