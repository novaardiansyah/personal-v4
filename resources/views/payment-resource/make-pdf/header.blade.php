@include('layout.header')

<h1 class="bottom-0">{{ $title }}</h1>

<table width="100%">
  <tr>
    <td align="left">
      <p class="vertical-0 text-muted">Dicetak Pada: {{ $now }} <br>oleh {{ $user->name }}</p>
    </td>
    <td align="right">
      <p class="vertical-0 text-muted">Periode: {{ $periode }}</p>
    </td>
  </tr>
</table>

<hr>

<table class="table table-bordered table-sm" style="margin-top: 15px;">
  <thead>
    <tr>
      <th scope="col" style="text-align: center;">#</th>
      <th scope="col" style="white-space: nowrap;">ID Transaksi</th>
      <th scope="col" style="white-space: nowrap;">Tanggal</th>
      <th scope="col">Akun</th>
      <th scope="col" style="width: 300px;">Catatan</th>
      <th scope="col" style="white-space: nowrap; text-align: right;">Transfer</th>
      <th scope="col" style="white-space: nowrap; text-align: right;">Pemasukan</th>
      <th scope="col" style="white-space: nowrap; text-align: right;">Pengeluaran</th>
    </tr>
  </thead>
  <tbody>
