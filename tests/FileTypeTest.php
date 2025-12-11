<?php
use PHPUnit\Framework\TestCase;

/**
 * Kelas pengujian untuk validasi integrasi API Gemini.
 * * Catatan: Asumsikan GEMINI_API_KEY telah diatur sebagai environment variable
 * dan file yang diuji ('process.php' atau sejenisnya) ada.
 */
class ApiTest extends TestCase
{
    // Konfigurasi API
    private $apiKey;
    private $apiUrlBase = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private $model = 'gemini-2.5-flash';
    
    // File yang harus ada di project Anda (sesuaikan jika nama berbeda)
    private $projectFiles = [
        'index.html',
        'process.php' // Tambahkan file test ini sendiri
    ];

    /**
     * Metode setup dijalankan sebelum setiap test case.
     */
    protected function setUp(): void
    {
        // Ambil API Key dari environment variable
        $this->apiKey = getenv('GEMINI_API_KEY');
    }

    // --- a. file exist ---
    public function test_a_project_files_exist()
    {
        echo "\n[TEST A] Memeriksa keberadaan file proyek...";
        foreach ($this->projectFiles as $file) {
            $this->assertFileExists($file, "File $file tidak ditemukan!");
            echo "."; // Indikator sukses
        }
        echo " OK.";
    }

    // --- b. valid syntax ---
    // Menggunakan regex sederhana untuk memastikan kode PHP mengandung tag <?php
    public function test_b_php_files_contain_php_code()
    {
        echo "\n[TEST B] Memeriksa sintaks dasar PHP...";
        $phpFiles = array_filter($this->projectFiles, function($file) {
            return pathinfo($file, PATHINFO_EXTENSION) === 'php';
        });

        foreach ($phpFiles as $file) {
            $content = file_get_contents($file);
            $this->assertStringContainsString('<?php', $content, "File $file tidak mengandung tag <?php!");
            echo ".";
        }
        echo " OK.";
    }
    
    // --- c. API Key tidak boleh kosong ---
    public function test_c_api_key_is_not_empty()
    {
        echo "\n[TEST C] Memeriksa apakah API Key telah disetel...";
        $this->assertNotEmpty($this->apiKey, "API Key (GEMINI_API_KEY) tidak boleh kosong. Harap atur sebagai environment variable.");
        echo " OK.";
    }

    // --- d. valid JSON response & e. Response Code harus 200 ---
    // Kedua tes ini digabung dalam satu panggilan API untuk efisiensi.
    public function test_d_and_e_api_call_succeeds_and_returns_valid_json()
    {
        echo "\n[TEST D&E] Memanggil API Gemini dan memvalidasi respon...";

        if (empty($this->apiKey)) {
            $this->markTestSkipped('Tes dilewati karena GEMINI_API_KEY kosong.');
        }

        $url = $this->apiUrlBase . $this->model . ':generateContent?key=' . $this->apiKey;

        // Payload permintaan sederhana
        $payload = json_encode([
            'contents' => [['parts' => [['text' => 'Hello.']]]]
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // --- e. Response Code harus 200 ---
        echo "\n  > Memvalidasi Response Code...";
        $this->assertEquals(200, $httpCode, 
            "Kode Respon HTTP yang diharapkan 200, didapat $httpCode. Respons: $response");
        echo " OK.";
        
        // --- d. valid JSON response ---
        echo "\n  > Memvalidasi format JSON...";
        $responseData = json_decode($response, true);
        
        // Memastikan decoding JSON berhasil
        $this->assertNotNull($responseData, "Respon API bukan format JSON yang valid: $response");
        
        // Memastikan respons JSON memiliki struktur dasar Gemini (kandidat)
        $this->assertArrayHasKey('candidates', $responseData, 
            "Respon JSON valid, tetapi tidak memiliki kunci 'candidates' yang diharapkan.");
        echo " OK.\n";
    }
}