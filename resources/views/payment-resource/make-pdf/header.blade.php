@include('layout.header')

<h1 class="bottom-0">{{ $title }}</h1>

<table width="100%">
  <tr>
    <td align="left"><p class="vertical-0 text-muted">Dicetak Pada: {{ $now }} <br>oleh {{ $user->name }}</p></td>
    <td align="right"><p class="vertical-0 text-muted">Periode: {{ $periode }}</p></td>
  </tr>
</table>

<hr>

<table class="table table-bordered table-sm" style="margin-top: 15px;">
  <thead>
    <tr>
      <th scope="col">#</th>
      <th scope="col">ID Transaksi</th>
      <th scope="col">Tanggal</th>
      <th scope="col">Catatan</th>
      <th scope="col">Transfer</th>
      <th scope="col">Pemasukan</th>
      <th scope="col">Pengeluaran</th>
    </tr>
  </thead>
  <tbody>
