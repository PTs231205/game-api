<?php
// Session-based Language Manager
class LanguageManager {
    private $lang;
    private $translations;
    private $baseDir;

    public function __construct($baseDir = __DIR__ . '/../languages') {
        session_start();
        $this->baseDir = $baseDir;
        $this->lang = 'en'; // Default

        if (isset($_GET['lang'])) {
            $this->setLanguage($_GET['lang']);
        } elseif (isset($_SESSION['lang'])) {
            $this->lang = $_SESSION['lang'];
        }

        $this->loadTranslations();
    }

    public function setLanguage($lang) {
        $allowed = ['en', 'hi', 'ar']; // Allowed languages
        if (in_array($lang, $allowed)) {
            $this->lang = $lang;
            $_SESSION['lang'] = $lang;
        }
    }

    private function loadTranslations() {
        $file = $this->baseDir . '/' . $this->lang . '.php';
        if (file_exists($file)) {
            $this->translations = require $file;
        } else {
            // Fallback to English
            $this->translations = require $this->baseDir . '/en.php';
        }
    }

    public function trans($key) {
        return isset($this->translations[$key]) ? $this->translations[$key] : $key;
    }

    public function getCurrentLang() {
        return $this->lang;
    }
}
