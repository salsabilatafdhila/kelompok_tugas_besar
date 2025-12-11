<?php
use PHPUnit\Framework\TestCase;

// Definisikan konstanta untuk kemudahan pengujian
define('INDEX_FILE_PATH', 'index.php'); // Asumsi path file front-end Anda

class FileTypeTest extends TestCase
{
    private $projectFiles = [
        API_FILE_PATH,  // process.php (didefinisikan di ApiTest.php)
        INDEX_FILE_PATH // index.php (file yang berisi HTML/JS)
    ];

    /**
     * Test Case A: Memastikan semua file yang dibutuhkan ada.
     */
    public function test_a_files_exist()
    {
        foreach ($this->projectFiles as $file) {
            $this->assertFileExists($file, "File $file tidak ditemukan!");
        }
    }

    /**
     * Test Case B: Memastikan file PHP memiliki tag pembuka.
     */
    public function test_b_php_files_contain_php_code()
    {
        if (pathinfo(API_FILE_PATH, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents(API_FILE_PATH);
            $this->assertStringContainsString('<?php', $content, "File " . API_FILE_PATH . " tidak mengandung tag pembuka PHP.");
        }
    }

    /**
     * Test Case C: Memastikan file index.php mengandung tag HTML dasar.
     */
    public function test_c_html_file_contains_basic_tags()
    {
        if (pathinfo(INDEX_FILE_PATH, PATHINFO_EXTENSION) === 'php' || pathinfo(INDEX_FILE_PATH, PATHINFO_EXTENSION) === 'html') {
            $content = file_get_contents(INDEX_FILE_PATH);

            // Cek apakah ada tag HTML dasar (ini juga mencakup file .php yang berfungsi sebagai halaman utama)
            $this->assertMatchesRegularExpression(
                '/<html|<head|<body/i',
                $content,
                "File " . INDEX_FILE_PATH . " tidak memiliki struktur HTML dasar yang valid!"
            );
        }
    }
}