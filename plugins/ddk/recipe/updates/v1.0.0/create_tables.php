<?php

namespace Ddk\Recipe\Updates;

use Winter\Storm\Database\Updates\Migration;
use Winter\Storm\Support\Facades\Schema;

class CreateTables extends Migration
{
    public function up()
    {
        // Ingredients table
        Schema::create('ddk_recipe_ingredients', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        // Units table
        Schema::create('ddk_recipe_units', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        // Categories table (nested)
        Schema::create('ddk_recipe_categories', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('title');
            $table->string('slug')->nullable()->index();
            $table->integer('parent_id')->unsigned()->index()->nullable();
            $table->integer('nest_left')->nullable();
            $table->integer('nest_right')->nullable();
            $table->integer('nest_depth')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Main recipes table
        Schema::create('ddk_recipes', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('public_id', 16)->unique()->index();
            $table->string('title');
            $table->string('slug')->index();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->integer('portions')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->integer('working_time_minutes')->nullable();
            $table->integer('cooking_time_minutes')->nullable();
            $table->integer('author_id')->unsigned()->nullable()->index();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->text('tip')->nullable();
            $table->timestamps();
        });

        // Recipe ingredients mapping table
        Schema::create('ddk_recipe_ingredients_map', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('recipe_id')->unsigned()->index();
            $table->integer('ingredient_id')->unsigned()->index();
            $table->integer('unit_id')->unsigned()->nullable()->index();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->foreign('recipe_id')
                ->references('id')
                ->on('ddk_recipes')
                ->onDelete('cascade');

            $table->foreign('ingredient_id')
                ->references('id')
                ->on('ddk_recipe_ingredients')
                ->onDelete('cascade');

            $table->foreign('unit_id')
                ->references('id')
                ->on('ddk_recipe_units')
                ->onDelete('set null');
        });

        // Recipe categories mapping table
        Schema::create('ddk_recipe_categories_map', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('recipe_id')->unsigned()->index();
            $table->integer('category_id')->unsigned()->index();

            $table->foreign('recipe_id')
                ->references('id')
                ->on('ddk_recipes')
                ->onDelete('cascade');

            $table->foreign('category_id')
                ->references('id')
                ->on('ddk_recipe_categories')
                ->onDelete('cascade');
        });

        // Recipe steps table
        Schema::create('ddk_recipe_steps', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('recipe_id')->unsigned()->index();
            $table->integer('step_number');
            $table->string('title')->nullable();
            $table->text('description');
            $table->timestamps();

            $table->foreign('recipe_id')
                ->references('id')
                ->on('ddk_recipes')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ddk_recipe_steps');
        Schema::dropIfExists('ddk_recipe_categories_map');
        Schema::dropIfExists('ddk_recipe_ingredients_map');
        Schema::dropIfExists('ddk_recipes');
        Schema::dropIfExists('ddk_recipe_categories');
        Schema::dropIfExists('ddk_recipe_units');
        Schema::dropIfExists('ddk_recipe_ingredients');
    }
}
