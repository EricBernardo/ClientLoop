<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedInteger('contact_limit')->default(500);
            $t->unsignedInteger('task_limit')->default(1000);
            $t->boolean('is_default')->default(false);
            $t->timestamps();
        });
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('timezone')->default('America/Sao_Paulo');
            $t->string('status')->default('trial');
            $t->unsignedTinyInteger('confirmation_hours')->default(24);
            $t->unsignedSmallInteger('reactivation_months')->default(6);
            $t->timestamps();
        });
        Schema::table('users', fn (Blueprint $t) => $t->foreign('company_id')->references('id')->on('companies')->nullOnDelete());
        Schema::create('company_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->constrained();
            $t->string('status')->default('trial');
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable();
            $t->timestamps();
            $t->unique('company_id');
        });
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('phone', 20);
            $t->text('notes')->nullable();
            $t->timestamp('last_activity_at')->nullable();
            $t->timestamp('next_return_at')->nullable();
            $t->timestamp('opted_out_at')->nullable();
            $t->string('opt_out_note')->nullable();
            $t->timestamps();
            $t->unique(['company_id', 'phone']);
        });
        Schema::create('services', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->decimal('suggested_price', 12, 2)->nullable();
            $t->unsignedSmallInteger('return_interval_months')->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['company_id', 'name']);
        });
        Schema::create('appointments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('scheduled_at');
            $t->string('status')->default('scheduled');
            $t->timestamps();
            $t->index(['company_id', 'scheduled_at', 'status']);
        });
        Schema::create('message_templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('type');
            $t->text('body');
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->unique(['company_id', 'name']);
        });
        Schema::create('campaigns', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('message_template_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('type');
            $t->string('status')->default('draft');
            $t->json('filters')->nullable();
            $t->timestamp('starts_at')->nullable();
            $t->timestamps();
        });
        Schema::create('campaign_recipients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('contact_task_id')->nullable();
            $t->string('status')->default('pending');
            $t->timestamps();
            $t->unique(['campaign_id', 'customer_id']);
        });
        Schema::create('contact_tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('message_template_id')->nullable()->constrained()->nullOnDelete();
            $t->string('type');
            $t->string('status')->default('pending');
            $t->string('priority')->default('normal');
            $t->timestamp('due_at');
            $t->text('rendered_message')->nullable();
            $t->string('outcome')->nullable();
            $t->text('outcome_note')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
            $t->index(['company_id', 'status', 'due_at']);
        });
        Schema::create('contact_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('contact_task_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('outcome')->nullable();
            $t->text('note')->nullable();
            $t->timestamp('attempted_at');
            $t->timestamps();
        });
        Schema::create('usage_records', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->string('period', 7);
            $t->unsignedInteger('contacts_count')->default(0);
            $t->unsignedInteger('tasks_count')->default(0);
            $t->timestamps();
            $t->unique(['company_id', 'period']);
        });
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('company_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('event');
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('properties')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'usage_records', 'contact_attempts', 'contact_tasks', 'campaign_recipients', 'campaigns', 'message_templates', 'appointments', 'services', 'customers', 'company_subscriptions'] as $t) {
            Schema::dropIfExists($t);
        } Schema::table('users', fn (Blueprint $t) => $t->dropConstrainedForeignId('company_id'));
        Schema::dropIfExists('companies');
        Schema::dropIfExists('plans');
    }
};
