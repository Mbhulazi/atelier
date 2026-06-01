# Skill 03: Database Schema

## Overview
Complete database migrations for all platform entities: paintings, videos, orders, grisaille analyses, and projects.

---

## Migration Files

### `database/migrations/xxxx_create_painter_profiles_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('painter_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->text('specialties')->nullable();
            $table->text('style')->nullable();
            $table->string('location')->nullable();
            $table->string('website')->nullable();
            $table->text('featured_image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('featured_order')->default(0);
            $table->json('social_links')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('painter_profiles');
    }
};
```

---

### `database/migrations/xxxx_create_paintings_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paintings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('medium')->nullable();
            $table->string('style')->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('depth', 8, 2)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->year('year_created')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_available']);
            $table->index(['user_id', 'is_featured']);
            $table->index('style');
            $table->index('medium');
        });

        Schema::create('painting_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('painting_id')->constrained()->cascadeOnDelete();
            $table->string('image_url');
            $table->string('thumbnail_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('painting_images');
        Schema::dropIfExists('paintings');
    }
};
```

---

### `database/migrations/xxxx_create_videos_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url');
            $table->string('thumbnail_url')->nullable();
            $table->string('hls_url')->nullable();
            $table->integer('duration')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->enum('category', ['technique', 'demo', 'tutorial', 'timelapse'])->default('technique');
            $table->boolean('is_published')->default(false);
            $table->boolean('is_free')->default(false);
            $table->json('tags')->nullable();
            $table->integer('view_count')->default(0);
            $table->integer('purchase_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_published']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
```

---

### `database/migrations/xxxx_create_orders_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('painter_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['painting', 'video', 'subscription']);
            $table->unsignedBigInteger('item_id');
            $table->decimal('amount', 10, 2);
            $table->decimal('commission_rate', 5, 2)->default(15.00);
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('net_amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'refunded',
                'partially_refunded',
            ])->default('pending');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_transfer_id')->nullable();
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();

            $table->index('buyer_id');
            $table->index('painter_id');
            $table->index('status');
            $table->index('stripe_payment_intent_id');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedBigInteger('item_id');
            $table->string('item_title');
            $table->decimal('price', 10, 2);
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
```

---

### `database/migrations/xxxx_create_grisaille_analyses_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grisaille_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_image_url');
            $table->string('processed_image_url')->nullable();
            $table->json('value_map');
            $table->json('value_percentages');
            $table->json('palette_recommendation');
            $table->json('glazing_suggestions');
            $table->string('pdf_url')->nullable();
            $table->integer('image_width')->nullable();
            $table->integer('image_height')->nullable();
            $table->integer('total_pixels')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grisaille_analyses');
    }
};
```

---

### `database/migrations/xxxx_create_projects_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['planned', 'in_progress', 'completed'])->default('planned');
            $table->text('cover_image')->nullable();
            $table->json('images')->nullable();
            $table->date('start_date')->nullable();
            $table->date('target_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->boolean('is_public')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
```

---

### `database/migrations/xxxx_create_purchases_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purchasable_type');
            $table->unsignedBigInteger('purchasable_id');
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'purchasable_type', 'purchasable_id']);
            $table->index('purchasable_type', 'purchasable_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
```

---

## Seeders

### `database/seeders/RolesSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::create(['name' => 'manage paintings']);
        Permission::create(['name' => 'manage videos']);
        Permission::create(['name' => 'manage projects']);
        Permission::create(['name' => 'view analytics']);
        Permission::create(['name' => 'manage orders']);
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'manage platform']);
        Permission::create(['name' => 'use grisaille tool']);

        $collector = Role::create(['name' => 'collector']);
        $collector->givePermissionTo(['use grisaille tool']);

        $painter = Role::create(['name' => 'painter']);
        $painter->givePermissionTo([
            'manage paintings',
            'manage videos',
            'manage projects',
            'view analytics',
            'manage orders',
            'use grisaille tool',
        ]);

        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());
    }
}
```

### `database/seeders/DatabaseSeeder.php`

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@atelier.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        $painter = User::factory()->create([
            'name' => 'Maria Gonzalez',
            'email' => 'maria@atelier.com',
            'password' => Hash::make('password'),
            'role' => 'painter',
            'bio' => 'Contemporary oil painter inspired by the California coast.',
        ]);
        $painter->assignRole('painter');

        $collector = User::factory()->create([
            'name' => 'James Wilson',
            'email' => 'james@atelier.com',
            'password' => Hash::make('password'),
            'role' => 'collector',
        ]);
        $collector->assignRole('collector');
    }
}
```

---

## Model Relationships Summary

```
User (1) ──── (1) PainterProfile
User (1) ──── (∞) Painting
User (1) ──── (∞) Video
User (1) ──── (∞) Project
User (1) ──── (∞) Order (as buyer)
User (1) ──── (∞) Order (as painter)
User (1) ──── (∞) GrisailleAnalysis
User (∞) ──── (∞) Video (purchased via Purchase)

Painting (1) ──── (∞) PaintingImage
Order (1) ──── (∞) OrderItem
```

---

## Verification Checklist

- [ ] All migrations run without errors
- [ ] Roles and permissions seeded correctly
- [ ] Foreign keys enforced
- [ ] Indexes on frequently queried columns
- [ ] Soft deletes on paintings, videos, projects

---

## Next Steps

Proceed to [Skill 04: Landing Page](./04-landing-page.md) for the frontend marketing site.
