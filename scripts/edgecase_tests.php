<?php
// scripts/edgecase_tests.php
$base = 'http://127.0.0.1:8000/Public/api.php';
$cookie = sys_get_temp_dir() . '/sicokro_edge.cookies';

function http($url, $post = null, $cookieFile = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $res = curl_exec($ch);
    if ($res === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("HTTP error: $err");
    }
    curl_close($ch);
    return $res;
}

try {
    echo "LOGIN as super_admin...\n";
    $r = http($base . '?action=login', ['email' => 'apitest+local@local', 'password' => 'Test1234!'], $cookie);
    echo $r . "\n";
    $jr = json_decode($r, true);
    if (empty($jr['success'])) throw new Exception('Login failed');

    // pick sekolah id
    $r = http($base . '?action=list_sekolah', null, $cookie);
    $j = json_decode($r, true);
    $sekolah_id = $j['data'][0]['id'] ?? null;
    if (!$sekolah_id) throw new Exception('No sekolah found');
    echo "Using sekolah_id=$sekolah_id\n";

    // Create a tagihan for partial tests
    echo "CREATE TAGIHAN (100000)...\n";
    $r = http($base . '?action=create_tagihan', ['sekolah_id'=>$sekolah_id, 'kode_tagihan'=>'EDGE-TAG-1', 'deskripsi'=>'Edge test', 'jumlah'=>100000], $cookie);
    echo $r . "\n";
    $jr = json_decode($r, true);
    $tag1 = $jr['id'] ?? null;
    if (!$tag1) throw new Exception('Failed create tagihan');

    // Partial payment 40000
    echo "CREATE PEMBAYARAN partial 40000 (auto_confirm)...\n";
    $r = http($base . '?action=create_pembayaran', ['sekolah_id'=>$sekolah_id, 'tagihan_id'=>$tag1, 'jumlah'=>40000, 'auto_confirm'=>'1'], $cookie);
    echo $r . "\n";

    // Check tagihan status
    echo "GET TAGIHAN after partial...\n";
    $r = http($base . '?action=get_tagihan&id=' . urlencode($tag1), null, $cookie);
    echo $r . "\n";

    // Second payment 60000 -> should become paid
    echo "CREATE PEMBAYARAN remaining 60000 (auto_confirm)...\n";
    $r = http($base . '?action=create_pembayaran', ['sekolah_id'=>$sekolah_id, 'tagihan_id'=>$tag1, 'jumlah'=>60000, 'auto_confirm'=>'1'], $cookie);
    echo $r . "\n";

    echo "GET TAGIHAN after full payment...\n";
    $r = http($base . '?action=get_tagihan&id=' . urlencode($tag1), null, $cookie);
    echo $r . "\n";

    // INVALID ROLE: create a transient user with role 'siswa'
    echo "Creating test user with role 'siswa'...\n";
    $cfg = require __DIR__ . '/../Config.php';
    $db = $cfg['db'];
    $dsn = sprintf('mysql:host=%s;dbname=%s;port=%s;charset=%s', $db['host'], $db['dbname'], $db['port'] ?? 3306, $db['charset'] ?? 'utf8mb4');
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $email = 'apitest_noperm@local';
    $password = 'NoPerm123!';
    $hash = password_hash($password, PASSWORD_BCRYPT);
    // remove if exists
    $pdo->prepare('DELETE FROM users WHERE email = :e')->execute([':e'=>$email]);
    $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, nama, role, sekolah_id, created_at) VALUES (:email, :hash, :nama, :role, :sekolah, NOW())');
    $stmt->execute([':email'=>$email, ':hash'=>$hash, ':nama'=>'NoPerm User', ':role'=>'siswa', ':sekolah'=>$sekolah_id]);
    echo "Created user $email\n";

    // Login as the new user
    echo "LOGIN as siswa user...\n";
    // clear cookie file first
    @unlink($cookie);
    $r = http($base . '?action=login', ['email' => $email, 'password' => $password], $cookie);
    echo $r . "\n";

    echo "Attempt create_tagihan as siswa (should be forbidden)...\n";
    $r = http($base . '?action=create_tagihan', ['sekolah_id'=>$sekolah_id, 'kode_tagihan'=>'EDGE-TAG-NO', 'jumlah'=>1000], $cookie);
    echo $r . "\n";

    // File upload test: login back as super_admin
    echo "LOGIN back as super_admin...\n";
    @unlink($cookie);
    $r = http($base . '?action=login', ['email' => 'apitest+local@local', 'password' => 'Test1234!'], $cookie);

    echo "CREATE TAGIHAN for file-upload test...\n";
    $r = http($base . '?action=create_tagihan', ['sekolah_id'=>$sekolah_id, 'kode_tagihan'=>'EDGE-TAG-FILE', 'deskripsi'=>'File upload', 'jumlah'=>50000], $cookie);
    echo $r . "\n";
    $jr = json_decode($r, true);
    $tagfile = $jr['id'] ?? null;

    // create temporary pdf file
    $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'edge_bukti.pdf';
    file_put_contents($tmp, "%PDF-1.4\n%test\n");
    echo "Temp file: $tmp\n";

    // use curl manually to send multipart with file
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base . '?action=create_pembayaran');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    $post = [
        'sekolah_id' => $sekolah_id,
        'tagihan_id' => $tagfile,
        'jumlah' => 50000,
        'metode' => 'Transfer',
        'auto_confirm' => '1',
        'bukti' => new CURLFile($tmp, 'application/pdf', basename($tmp))
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    $res = curl_exec($ch);
    if ($res === false) { throw new Exception('Curl file upload error: '.curl_error($ch)); }
    curl_close($ch);
    echo "UPLOAD RESPONSE: $res\n";

    // List pembayaran and find file entry
    echo "LIST PEMBAYARAN for school...\n";
    $r = http($base . '?action=list_pembayaran&sekolah_id=' . urlencode($sekolah_id), null, $cookie);
    echo $r . "\n";

    echo "EDGECASE TESTS DONE\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
