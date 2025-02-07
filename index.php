<?php
// index.php


$dataFile = 'transactions.json';


if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([]));
}


$transactions = json_decode(file_get_contents($dataFile), true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan Keuangan Void Cloud</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: auto; }
    input, button, select { margin: 5px 0; display: block; width: 100%; padding: 8px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid black; padding: 8px; text-align: left; }
    .delete-btn { background-color: red; color: white; border: none; cursor: pointer; }
    canvas { margin-top: 20px; }
	img { border-radius:50%;width:120px;height:120px;justify-content:right; }
	.container {display:flex;}
	.right {position: relative;}
  </style>
</head>
<body>
      <div class="container">
  <img src="img/logo-void.jpg">
  <div style="display:block;"><h2>Web Aplikasi Laporan keuangan</h2><h2> Void Cloud</h2></div> </div>
  <input type="text" id="deskripsi" placeholder="Deskripsi">
  <input type="number" id="jumlah" placeholder="Jumlah (Rp)">
  <select id="tipe">
      <option value="pemasukan">Pemasukan</option>
      <option value="pengeluaran">Pengeluaran</option>
  </select>
  <button onclick="tambahTransaksi()">Tambah Transaksi</button>
  <h3>Saldo: Rp <span id="saldo">0</span></h3>
  <table>
      <thead>
          <tr>
              <th>Deskripsi</th>
              <th>Jumlah (Rp)</th>
              <th>Tipe</th>
              <th>Tanggal</th>
              <th>Aksi</th>
          </tr>
      </thead>
      <tbody id="daftar-transaksi">
          <?php foreach ($transactions as $trx) : ?>
          <tr>
              <td><?= htmlspecialchars($trx['deskripsi']) ?></td>
              <td><?= htmlspecialchars($trx['jumlah']) ?></td>
              <td><?= htmlspecialchars($trx['tipe']) ?></td>
              <td><?= htmlspecialchars($trx['tanggal']) ?></td>
              <td><button class="delete-btn" onclick="hapusTransaksi(<?= $trx['id'] ?>)">Hapus</button></td>
          </tr>
          <?php endforeach; ?>
      </tbody>
  </table>
  
  <canvas id="chartKeuangan"></canvas>
  
  <script>

    let transaksi = <?php echo json_encode($transactions); ?>;
    

    function updateTampilan() {
      let daftarTransaksi = document.getElementById("daftar-transaksi");
      let saldo = 0;
      daftarTransaksi.innerHTML = "";
      transaksi.forEach(trx => {
          saldo += parseFloat(trx.jumlah);
          let row = `<tr>
              <td>${trx.deskripsi}</td>
              <td>${trx.jumlah}</td>
              <td>${trx.tipe}</td>
              <td>${trx.tanggal}</td>
              <td><button class="delete-btn" onclick="hapusTransaksi(${trx.id})">Hapus</button></td>
          </tr>`;
          daftarTransaksi.innerHTML += row;
      });
      document.getElementById("saldo").textContent = saldo;
      updateChart();
    }
    

    async function tambahTransaksi() {
      let deskripsi = document.getElementById("deskripsi").value.trim();
      let jumlah = parseFloat(document.getElementById("jumlah").value);
      let tipe = document.getElementById("tipe").value;
      if (!deskripsi || isNaN(jumlah)) {
          return alert("Masukkan data yang valid!");
      }
      let formData = new FormData();
      formData.append('deskripsi', deskripsi);
      formData.append('jumlah', jumlah);
      formData.append('tipe', tipe);
      
      try {
          const response = await fetch('transaksi.php?action=add', {
              method: 'POST',
              body: formData
          });
          const result = await response.json();
          if (result.status === 'success') {

              transaksi.push(result.data);
              updateTampilan();
              document.getElementById("deskripsi").value = "";
              document.getElementById("jumlah").value = "";
          } else {
              alert(result.message || "Gagal menambahkan transaksi");
          }
      } catch (error) {
          console.error('Error adding transaction:', error);
      }
    }
    

    async function hapusTransaksi(id) {
      if (!confirm("Anda yakin ingin menghapus transaksi ini?")) return;
      let formData = new FormData();
      formData.append('id', id);
      try {
          const response = await fetch('transaksi.php?action=delete', {
              method: 'POST',
              body: formData
          });
          const result = await response.json();
          if (result.status === 'success') {

              transaksi = transaksi.filter(trx => trx.id != id);
              updateTampilan();
          } else {
              alert("Gagal menghapus transaksi");
          }
      } catch (error) {
          console.error('Error deleting transaction:', error);
      }
    }
    

    let ctx = document.getElementById("chartKeuangan").getContext("2d");
    let chartKeuangan = new Chart(ctx, {
        type: "bar",
        data: {
            labels: ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"],
            datasets: [
                { label: "Pemasukan", backgroundColor: "green", data: Array(12).fill(0) },
                { label: "Pengeluaran", backgroundColor: "red", data: Array(12).fill(0) }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    
    function updateChart() {
      let pemasukanBulanan = Array(12).fill(0);
      let pengeluaranBulanan = Array(12).fill(0);
      transaksi.forEach(trx => {
          let month = new Date(trx.tanggal).getMonth();
          if (trx.jumlah > 0) {
              pemasukanBulanan[month] += parseFloat(trx.jumlah);
          } else {
              pengeluaranBulanan[month] += Math.abs(parseFloat(trx.jumlah));
          }
      });
      chartKeuangan.data.datasets[0].data = pemasukanBulanan;
      chartKeuangan.data.datasets[1].data = pengeluaranBulanan;
      chartKeuangan.update();
    }
    

    updateTampilan();
  </script>
</body>
</html>
