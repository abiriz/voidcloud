<?php



$dataFile = 'transactions.json';


if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}


$transactions = json_decode(file_get_contents($dataFile), true);


function saveTransactions($transactions) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($transactions, JSON_PRETTY_PRINT));
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $deskripsi = $_POST['deskripsi'] ?? '';
        $jumlah = $_POST['jumlah'] ?? 0;
        $tipe = $_POST['tipe'] ?? '';
        $tanggal = date('Y-m-d');
        
        if (!$deskripsi || !is_numeric($jumlah) || !in_array($tipe, ['pemasukan', 'pengeluaran'])) {
            echo json_encode(['status' => 'error', 'message' => 'Input tidak valid']);
            exit;
        }

        if ($tipe === 'pengeluaran' && $jumlah > 0) {
            $jumlah = -$jumlah;
        }

        $newId = count($transactions) > 0 ? max(array_column($transactions, 'id')) + 1 : 1;
        $newTransaction = [
            'id' => $newId,
            'deskripsi' => $deskripsi,
            'jumlah' => $jumlah,
            'tipe' => $tipe,
            'tanggal' => $tanggal
        ];
        $transactions[] = $newTransaction;
        saveTransactions($transactions);
        echo json_encode(['status' => 'success', 'data' => $newTransaction]);
        exit;
    }
    
    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        if ($id) {
            $transactions = array_filter($transactions, function($trx) use ($id) {
                return $trx['id'] != $id;
            });

            $transactions = array_values($transactions);
            saveTransactions($transactions);
            echo json_encode(['status' => 'success']);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
            exit;
        }
    }
}
?>
