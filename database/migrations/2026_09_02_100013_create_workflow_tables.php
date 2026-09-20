<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void {
        Schema::create('notification_templates', function ($t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('event_type');
            $t->string('channel', 30); // in_app, email, sms
            $t->string('subject')->nullable();
            $t->text('body');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('notifications', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('type');
            $t->string('title');
            $t->text('message');
            $t->string('url')->nullable();
            $t->boolean('is_read')->default(false);
            $t->datetime('read_at')->nullable();
            $t->timestamps();
        });
        Schema::create('tasks', function ($t) {
            $t->id(); $t->uuid('uuid');
            $t->string('title');
            $t->text('description')->nullable();
            $t->foreignId('assigned_to')->constrained('users');
            $t->foreignId('created_by')->constrained('users');
            $t->string('assignable_type')->nullable();
            $t->unsignedBigInteger('assignable_id')->nullable();
            $t->date('due_date')->nullable();
            $t->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $t->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $t->timestamps();
        });
        Schema::create('comments', function ($t) {
            $t->id();
            $t->string('commentable_type');
            $t->unsignedBigInteger('commentable_id');
            $t->foreignId('user_id')->constrained();
            $t->text('body');
            $t->timestamps();
        });
        Schema::create('document_templates', function ($t) {
            $t->id();
            $t->string('name');
            $t->string('document_type');
            $t->enum('format', ['pdf', 'html', 'email']);
            $t->longText('template_content');
            $t->boolean('is_default')->default(false);
            $t->timestamps();
        });
    }
    public function down(): void {
    }
};