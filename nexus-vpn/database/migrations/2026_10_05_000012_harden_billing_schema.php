<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('cta');
            $table->softDeletes();
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            // Billing history must survive account removal: users are soft-deleted,
            // and a hard delete is refused while subscriptions exist.
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();

            // 'expired' is set by the scheduler; a plain string avoids enum rewrites per driver.
            $table->string('status', 20)->default('active')->change();
            $table->timestamp('started_at')->nullable()->after('status');
            $table->timestamp('canceled_at')->nullable()->after('current_period_end');
            $table->timestamp('trial_reminder_sent_at')->nullable();
            $table->timestamp('renewal_reminder_sent_at')->nullable();
            $table->softDeletes();

            $table->index(['status', 'current_period_end']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();

            $table->string('status', 20)->default('pending')->change();
            $table->string('failure_reason')->nullable()->after('status');
            $table->string('error_code', 100)->nullable()->after('failure_reason');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->string('refund_reason')->nullable()->after('refunded_at');
            $table->softDeletes();

            // Webhook and verify look payments up by the Chargily checkout id.
            $table->unique('provider_reference');
            $table->index(['status', 'paid_at']);
        });

        DB::table('subscriptions')->whereNull('started_at')->update(['started_at' => DB::raw('created_at')]);
        DB::table('subscriptions')->where('status', 'canceled')->whereNull('canceled_at')
            ->update(['canceled_at' => DB::raw('updated_at')]);

        // Keep only the newest current subscription per user before adding the guard index.
        $duplicates = DB::table('subscriptions')
            ->select('user_id')
            ->whereIn('status', ['active', 'trialing'])
            ->groupBy('user_id')
            ->havingRaw('count(*) > 1')
            ->pluck('user_id');

        foreach ($duplicates as $userId) {
            $keep = DB::table('subscriptions')->where('user_id', $userId)
                ->whereIn('status', ['active', 'trialing'])->orderByDesc('id')->value('id');
            DB::table('subscriptions')->where('user_id', $userId)
                ->whereIn('status', ['active', 'trialing'])->where('id', '!=', $keep)
                ->update(['status' => 'canceled', 'canceled_at' => now()]);
        }

        // One current (active/trialing) subscription per user, enforced by the database.
        if (in_array(DB::getDriverName(), ['sqlite', 'pgsql'], true)) {
            DB::statement("CREATE UNIQUE INDEX subscriptions_one_current_per_user ON subscriptions (user_id) WHERE status IN ('active', 'trialing') AND deleted_at IS NULL");
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['sqlite', 'pgsql'], true)) {
            DB::statement('DROP INDEX IF EXISTS subscriptions_one_current_per_user');
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['provider_reference']);
            $table->dropIndex(['status', 'paid_at']);
            $table->dropColumn(['failure_reason', 'error_code', 'refunded_at', 'refund_reason', 'deleted_at']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['status', 'current_period_end']);
            $table->dropColumn(['started_at', 'canceled_at', 'trial_reminder_sent_at', 'renewal_reminder_sent_at', 'deleted_at']);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'deleted_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
