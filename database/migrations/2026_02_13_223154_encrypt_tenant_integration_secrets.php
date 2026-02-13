<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tenant_integrations')->whereNotNull('secret')->orderBy('id')->each(function ($row) {
            try {
                Crypt::decryptString($row->secret);
            } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                DB::table('tenant_integrations')
                    ->where('id', $row->id)
                    ->update(['secret' => Crypt::encryptString($row->secret)]);
            }
        });
    }

    public function down(): void
    {
        // Irreversible: encrypted values cannot be converted back to plaintext
        // without knowing the original values
    }
};
