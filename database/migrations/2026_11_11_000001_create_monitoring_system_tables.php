<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Traffic Logs (genuine HTTP request metrics)
        Schema::create('traffic_logs', function (Blueprint $table) {
            $table->id();
            $table->string('session_hash', 64)->index();
            $table->string('url', 500);
            $table->string('route_name', 100)->nullable();
            $table->string('method', 10);
            $table->unsignedSmallInteger('status_code')->index();
            $table->decimal('response_time_ms', 8, 2);
            $table->unsignedInteger('inbound_bytes')->default(0);
            $table->unsignedInteger('outbound_bytes')->default(0);
            $table->string('device_type', 30)->default('Desktop');
            $table->string('browser', 50)->default('Unknown');
            $table->string('os', 50)->default('Unknown');
            $table->string('country_code', 5)->nullable();
            $table->string('country_name', 100)->nullable();
            $table->string('referer', 500)->nullable();
            $table->boolean('is_error')->default(false)->index();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['created_at', 'status_code']);
            $table->index(['created_at', 'session_hash']);
            $table->index(['created_at', 'is_error']);
        });

        // 2. Performance Logs (real browser Web Vitals & page loads)
        Schema::create('performance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500);
            $table->decimal('fcp', 8, 2)->nullable(); // First Contentful Paint (ms)
            $table->decimal('lcp', 8, 2)->nullable(); // Largest Contentful Paint (ms)
            $table->decimal('inp', 8, 2)->nullable(); // Interaction to Next Paint (ms)
            $table->decimal('cls', 8, 4)->nullable(); // Cumulative Layout Shift
            $table->decimal('ttfb', 8, 2)->nullable(); // Time to First Byte (ms)
            $table->decimal('dom_loaded', 8, 2)->nullable(); // DOM Content Loaded (ms)
            $table->decimal('fully_loaded', 8, 2)->nullable(); // Fully Loaded Time (ms)
            $table->decimal('response_time_ms', 8, 2)->nullable();
            $table->enum('classification', ['excellent', 'good', 'moderate', 'slow', 'critical'])->default('good');
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['created_at', 'classification']);
        });

        // 3. Server Metrics (genuine hardware and database monitoring)
        Schema::create('server_metrics', function (Blueprint $table) {
            $table->id();
            $table->decimal('cpu_usage', 5, 2)->nullable(); // %
            $table->decimal('ram_usage', 5, 2)->nullable(); // %
            $table->unsignedBigInteger('ram_used_bytes')->nullable();
            $table->unsignedBigInteger('ram_total_bytes')->nullable();
            $table->decimal('disk_usage', 5, 2)->nullable(); // %
            $table->unsignedBigInteger('disk_used_bytes')->nullable();
            $table->unsignedBigInteger('disk_total_bytes')->nullable();
            $table->decimal('db_response_time_ms', 8, 2)->nullable();
            $table->unsignedInteger('db_connections')->nullable();
            $table->string('status', 20)->default('operational'); // operational, warning, critical, offline
            $table->timestamp('created_at')->nullable()->index();
        });

        // 4. Monitoring Alerts (real automatic anomaly detection)
        Schema::create('monitoring_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50); // traffic_spike, bandwidth_spike, high_response_time, http_error_spike, high_cpu, high_ram, slow_database, outage
            $table->enum('severity', ['informational', 'warning', 'critical'])->default('warning');
            $table->string('title', 255);
            $table->text('message');
            $table->string('affected_service', 100)->nullable();
            $table->string('threshold_value', 100)->nullable();
            $table->string('actual_value', 100)->nullable();
            $table->enum('status', ['active', 'acknowledged', 'resolved'])->default('active');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['status', 'severity', 'created_at']);
        });

        // 5. Uptime Logs (genuine service availability health checks)
        Schema::create('uptime_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['operational', 'degraded', 'outage'])->default('operational');
            $table->unsignedSmallInteger('http_code')->nullable();
            $table->decimal('response_time_ms', 8, 2)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at')->nullable()->index();
        });

        // 6. Monitoring Settings (configurable alert thresholds & retention)
        Schema::create('monitoring_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_settings');
        Schema::dropIfExists('uptime_logs');
        Schema::dropIfExists('monitoring_alerts');
        Schema::dropIfExists('server_metrics');
        Schema::dropIfExists('performance_logs');
        Schema::dropIfExists('traffic_logs');
    }
};
