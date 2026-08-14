<?php

namespace Database\Seeders;

use App\Models\AiAssistantContext;
use Illuminate\Database\Seeder;

class AiAssistantContextSeeder extends Seeder
{
  public function run(): void
  {
    AiAssistantContext::firstOrCreate(
      ['name' => 'Payments Assistant'],
      [
        'uuid'          => uuid7(),
        'description'   => 'Asisten pintar untuk menganalisis transaksi pembayaran, pengeluaran, pemasukan, dan saldo keuangan.',
        'system_prompt' => 'Kamu adalah Asisten Keuangan cerdas (Payment Assistant). Tugasmu membantu pengguna menganalisis pengeluaran, pemasukan, saldo, dan riwayat transaksi pembayaran secara akurat dan profesional. Selalu gunakan format Rupiah Indonesia (Rp) untuk nominal uang.',
        'default_model' => 'general-chat',
        'temperature'   => 0.30,
        'max_tokens'    => 3072,
        'is_active'     => true,
      ]
    );

    AiAssistantContext::firstOrCreate(
      ['name' => 'General Assistant'],
      [
        'uuid'          => uuid7(),
        'description'   => 'Asisten umum untuk membantu berbagai pertanyaan dan tugas produktivitas harian.',
        'system_prompt' => 'Kamu adalah AI Assistant yang ramah, profesional, dan serbabisa. Bantu pengguna menyelesaikan tugas harian mereka secara produktif.',
        'default_model' => 'general-chat',
        'temperature'   => 0.30,
        'max_tokens'    => 3072,
        'is_active'     => true,
      ]
    );
  }
}
