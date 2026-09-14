<?php
// Disable WHMCS fatal error hijacks
$GLOBALS['customadminpath'] = 'admin';
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../includes/gatewayfunctions.php';

$tests = []; $beforeEach = []; $afterAll = [];

function test($description, $closure) {
    global $tests; $tests[] = ['desc' => $description, 'fn' => $closure];
}
function beforeEach($closure) {
    global $beforeEach; $beforeEach[] = $closure;
}
function afterAll($closure) {
    global $afterAll; $afterAll[] = $closure;
}
function expect($value) {
    return new class($value) {
        public $val;
        public $isNot = false;
        public function __construct($v) { $this->val = $v; }
        public function __get($name) {
            if ($name === 'not') { $this->isNot = true; return $this; }
            throw new Exception("Property $name does not exist");
        }
        public function toBe($expected) {
            $pass = $this->val === $expected;
            if ($this->isNot) $pass = !$pass;
            if (!$pass) throw new Exception("Expected " . var_export($expected, true) . " but got " . var_export($this->val, true));
            return $this;
        }
        public function toBeFalse() {
            return $this->toBe(false);
        }
        public function toBeTrue() {
            return $this->toBe(true);
        }
        public function toContain($expected) {
            $pass = strpos((string)$this->val, (string)$expected) !== false;
            if ($this->isNot) $pass = !$pass;
            if (!$pass) throw new Exception("Expected string to contain '$expected'");
            return $this;
        }
    };
}

$files = array_merge(glob(__DIR__ . '/tests/Unit/*.php'), glob(__DIR__ . '/tests/Integration/*.php'));
foreach ($files as $file) {
    require_once $file;
}

echo "🚀 PagHiper WHMCS Test Suite\n";
echo "============================\n\n";
$passed = 0; $failed = 0;
foreach ($tests as $t) {
    try {
        foreach ($beforeEach as $b) $b();
        $t['fn']();
        echo "✅ \033[32mPASS\033[0m {$t['desc']}\n";
        $passed++;
    } catch (Exception $e) {
        echo "❌ \033[31mFAIL\033[0m {$t['desc']}\n";
        echo "   -> " . $e->getMessage() . "\n";
        $failed++;
    }
}
foreach ($afterAll as $a) $a();
echo "\n🏁 Concluido: $passed passaram, $failed falharam.\n";
if ($failed > 0) exit(1);
