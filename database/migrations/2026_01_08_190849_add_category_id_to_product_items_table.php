<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('name')->constrained('categories')->nullOnDelete();
        });

        // Migrate Data
        $products = DB::table('product_items')->get();
        foreach ($products as $product) {
            if ($product->category) {
                // Find or create category
                $slug = Str::slug($product->category);
                $categoryId = DB::table('categories')->where('slug', $slug)->value('id');

                if (!$categoryId) {
                     $categoryId = DB::table('categories')->insertGetId([
                        'name' => $product->category,
                        'slug' => $slug,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Update product
                DB::table('product_items')->where('id', $product->id)->update(['category_id' => $categoryId]);
            }
        }

        // Drop old column
        Schema::table('product_items', function (Blueprint $table) {
             $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_items', function (Blueprint $table) {
            $table->string('category')->nullable();
        });
        
         // Restore Data (Optional - usually hard to reverse perfectly without backup)
         // But for structure:
        Schema::table('product_items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
