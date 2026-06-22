<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = (string) config('mail-guard.table', 'mail_guard_messages');

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->string('mailer')->nullable()->index();
            $table->string('subject')->nullable();
            $table->string('source')->nullable()->index();
            $table->text('sender')->nullable();
            $table->text('recipients')->nullable();
            $table->text('cc')->nullable();
            $table->text('bcc')->nullable();
            $table->text('reply_to')->nullable();
            $table->json('to_addresses')->nullable();
            $table->longText('html_body')->nullable();
            $table->longText('text_body')->nullable();
            $table->longText('headers')->nullable();
            $table->json('attachments')->nullable();
            $table->string('risk_level')->default('ok')->index();
            $table->unsignedInteger('findings_count')->default(0);
            $table->boolean('blocked')->default(false)->index();
            $table->timestamp('captured_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $tableName = (string) config('mail-guard.table', 'mail_guard_messages');

        if ($tableName !== 'mail_guard_messages') {
            return;
        }

        Schema::dropIfExists('mail_guard_messages');
    }
};
