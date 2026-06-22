<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = (string) config('mail-guard.findings_table', 'mail_guard_findings');
        $messagesTable = (string) config('mail-guard.table', 'mail_guard_messages');

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($messagesTable): void {
            $table->id();
            $table->foreignId('message_id')->constrained($messagesTable)->cascadeOnDelete();
            $table->string('rule_id')->index();
            $table->string('severity')->index();
            $table->string('confidence');
            $table->string('title');
            $table->text('detail')->nullable();
            $table->string('location')->nullable();
            $table->text('snippet')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        $tableName = (string) config('mail-guard.findings_table', 'mail_guard_findings');

        if ($tableName !== 'mail_guard_findings') {
            return;
        }

        Schema::dropIfExists('mail_guard_findings');
    }
};
