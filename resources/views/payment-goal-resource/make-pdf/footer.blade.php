  </tbody>
  <tfoot>
    <tr>
      <td colspan="4" style="text-align: center; font-weight: bold;">Total</td>
      <td style="font-weight: bold;">{{ toIndonesianCurrency($total_target) }}</td>
      <td style="font-weight: bold;">{{ toIndonesianCurrency($total_amount) }}</td>
      <td colspan="3"></td>
    </tr>
    <tr>
      <td colspan="3" style="text-align: center; font-weight: bold;">Jumlah Target Selesai</td>
      <td style="font-weight: bold;">{{ $completed_count }}</td>
      <td colspan="5"></td>
    </tr>
    <tr>
      <td colspan="3" style="text-align: center; font-weight: bold;">Jumlah Target Aktif</td>
      <td style="font-weight: bold;">{{ $active_count }}</td>
      <td colspan="5"></td>
    </tr>
  </tfoot>
</table>
