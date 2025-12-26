<?php
// scripts/integration_tagihan_pembayaran.php
$base = 'http://127.0.0.1:8000/Public/api.php';
$cookie = sys_get_temp_dir() . '/sicokro_integ.cookies';

function http($url, $post = null, $cookieFile = null, $files = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    if ($files !== null) {
        // assume $post is array and $files is ['bukti' => '/path/to/file']
        // Build multipart form
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
    echo "LOGIN...\n";
    $r = http($base . '?action=login', ['email' => 'apitest+local@local', 'password' => 'Test1234!'], $cookie);
    echo $r . "\n";
    $jr = json_decode($r, true);
    if (empty($jr['success'])) throw new Exception('Login failed');

    echo "LIST SEKOLAH...\n";
    $r = http($base . '?action=list_sekolah', null, $cookie);
    echo $r . "\n";
    $j = json_decode($r, true);
    $sekolah_id = $j['data'][0]['id'] ?? null;
    if (!$sekolah_id) throw new Exception('No sekolah found');
    echo "using sekolah_id=$sekolah_id\n";

    // Create Tagihan
    echo "CREATE TAGIHAN...\n";
    $tagPayload = ['sekolah_id' => $sekolah_id, 'kode_tagihan' => 'SMOKE-TAG-1', 'deskripsi' => 'Integration test tagihan', 'jumlah' => 75000];
    $r = http($base . '?action=create_tagihan', $tagPayload, $cookie);
    echo $r . "\n";
    $jr = json_decode($r, true);
    $tag_id = $jr['id'] ?? null;
    if (!$tag_id) throw new Exception('Create tagihan failed');

    // Confirm tagihan initial status
    echo "GET TAGIHAN before payment...\n";
    $r = http($base . '?action=get_tagihan&id=' . urlencode($tag_id), null, $cookie);
    echo $r . "\n";

    // Create Pembayaran with auto_confirm=1
    echo "CREATE PEMBAYARAN (auto_confirm)...\n";
    $payPayload = ['sekolah_id' => $sekolah_id, 'tagihan_id' => $tag_id, 'jumlah' => 75000, 'metode' => 'Transfer Bank', 'auto_confirm' => '1'];
    $r = http($base . '?action=create_pembayaran', $payPayload, $cookie);
    echo $r . "\n";
    $jr = json_decode($r, true);
    $pemb_id = $jr['id'] ?? null;
    if (!$pemb_id) throw new Exception('Create pembayaran failed');

    // Get pembayaran list
    echo "LIST PEMBAYARAN...\n";
    $r = http($base . '?action=list_pembayaran&sekolah_id=' . urlencode($sekolah_id), null, $cookie);
    echo $r . "\n";

    // Re-fetch tagihan to check status and total_paid
    echo "GET TAGIHAN after payment...\n";
    $r = http($base . '?action=get_tagihan&id=' . urlencode($tag_id), null, $cookie);
    echo $r . "\n";

    // Cleanup: delete pembayaran? API does not expose delete pembayaran; we can leave it.
    // Delete tagihan (if endpoint exists) - not implemented; skip cleanup.

    echo "INTEGRATION TESTS DONE\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
