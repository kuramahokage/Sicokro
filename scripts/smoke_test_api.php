<?php
// scripts/smoke_test_api.php
$base = 'http://127.0.0.1:8000/Public/api.php';
$cookie = sys_get_temp_dir() . '/sicokro_smoke.cookies';

function http($url, $post = null, $cookieFile = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
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
    echo "LOGIN...\n";
    $r = http($base . '?action=login', ['email' => 'apitest+local@local', 'password' => 'Test1234!'], $cookie);
    echo $r . "\n";

    echo "LIST SEKOLAH...\n";
    $r = http($base . '?action=list_sekolah', null, $cookie);
    echo $r . "\n";
    $j = json_decode($r, true);
    $sekolah_id = $j['data'][0]['id'] ?? null;
    if (!$sekolah_id) {
        echo "No sekolah found to attach Aset/RKAS. Aborting.\n";
        exit(1);
    }
    echo "Using sekolah_id=$sekolah_id\n";

    echo "CREATE ASET...\n";
    $r = http($base . '?action=create_aset', ['sekolah_id' => $sekolah_id, 'kode_aset'=>'SMK-SMOKE-1', 'nama'=>'Smoke Aset', 'kategori'=>'Test', 'lokasi'=>'Gudang', 'nilai_perolehan'=>12345], $cookie);
    echo $r . "\n";
    $jr = json_decode($r, true);
    $aset_id = $jr['id'] ?? null;

    echo "LIST ASET...\n";
    $r = http($base . '?action=list_aset&sekolah_id=' . urlencode($sekolah_id), null, $cookie);
    echo $r . "\n";

    echo "CREATE RKAS...\n";
    $r = http($base . '?action=create_rkas', ['sekolah_id' => $sekolah_id, 'tahun' => date('Y'), 'anggaran_total' => 100000, 'realisasi_total' => 0], $cookie);
    echo $r . "\n";
    $jr = json_decode($r, true);
    $rkas_id = $jr['id'] ?? null;

    echo "LIST RKAS...\n";
    $r = http($base . '?action=list_rkas&sekolah_id=' . urlencode($sekolah_id), null, $cookie);
    echo $r . "\n";

    if ($aset_id) {
        echo "DELETE ASET $aset_id...\n";
        $r = http($base . '?action=delete_aset', ['id' => $aset_id], $cookie);
        echo $r . "\n";
    }
    if ($rkas_id) {
        echo "DELETE RKAS $rkas_id...\n";
        $r = http($base . '?action=delete_rkas', ['id' => $rkas_id], $cookie);
        echo $r . "\n";
    }

    echo "SMOKE TESTS DONE\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
