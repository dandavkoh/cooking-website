<?php

namespace Ddk\Api\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use System\Models\File;
use Ddk\Recipe\Models\Recipe;
use Ddk\Recipe\Models\Ingredient;
use Ddk\Recipe\Models\Unit;
use Ddk\Recipe\Models\Step;
use Ddk\Recipe\Models\RecipeIngredient;
use Ddk\Api\Http\Requests\RecipeStoreRequest;
use Ddk\Api\Exceptions\ApiException;
use Exception;
use Carbon\Carbon;

/**
 * Recipe Service
 *
 * Handles all business logic for recipe creation and management
 * through the API. Ensures transactional integrity and proper
 * handling of all relationships.
 */
class RecipeService
{
    /**
     * Create a new recipe with all relationships
     *
     * This method handles the complete recipe creation including:
     * - Unique slug generation
     * - Ingredient lookup/creation
     * - Unit lookup/creation
     * - Step creation with auto-numbering
     * - Category assignment
     * - Image upload from base64
     * - Publishing logic
     *
     * All operations are wrapped in a database transaction.
     *
     * @param array $data Validated request data
     * @return Recipe
     * @throws ApiException
     */
    public function createRecipe(array $data): Recipe
    {
        try {
            return DB::transaction(function () use ($data) {
                // 1. Generate unique slug
                $slug = $this->generateUniqueSlug($data['slug'] ?? null, $data['title']);

                // 2. Map difficulty from number to string
                $difficulty = RecipeStoreRequest::mapDifficulty($data['difficulty']);

                // 3. Handle publishing
                $isPublished = $data['published'] ?? false;
                $publishedAt = $isPublished ? Carbon::now() : null;

                // 4. Create the recipe
                $recipe = Recipe::create([
                    'title' => $data['title'],
                    'slug' => $slug,
                    'subtitle' => $data['subtitle'] ?? null,
                    'description' => $data['description'] ?? null,
                    'tip' => $data['tip'] ?? null,
                    'portions' => $data['portions'] ?? null,
                    'difficulty' => $difficulty,
                    'working_time_minutes' => $data['working_time_minutes'] ?? null,
                    'cooking_time_minutes' => $data['cooking_time_minutes'] ?? null,
                    'author_id' => $data['author_id'],
                    'is_published' => $isPublished,
                    'published_at' => $publishedAt,
                ]);

                // 5. Attach categories
                $this->attachCategories($recipe, $data['categories']);

                // 6. Create ingredients with units
                $this->createIngredients($recipe, $data['ingredients']);

                // 7. Create steps with auto-numbering
                $this->createSteps($recipe, $data['steps']);

                // 8. Handle images
                if (!empty($data['images'])) {
                    $this->uploadImages($recipe, $data['images']);
                }

                // Reload recipe with all relationships
                return $recipe->fresh([
                    'categories',
                    'recipe_ingredients.ingredient',
                    'recipe_ingredients.unit',
                    'steps',
                    'images',
                    'author',
                ]);
            });
        } catch (Exception $e) {
            throw new ApiException(
                trans('ddk.api::lang.errors.recipe_creation_failed') . ': ' . $e->getMessage(),
                500,
                'RECIPE_CREATION_FAILED'
            );
        }
    }

    /**
     * Generate a unique slug
     *
     * If a slug is provided, ensures it's unique by appending numbers.
     * If no slug is provided, generates one from the title.
     */
    protected function generateUniqueSlug(?string $providedSlug, string $title): string
    {
        $baseSlug = $providedSlug ? Str::slug($providedSlug) : Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (Recipe::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Attach categories to recipe
     */
    protected function attachCategories(Recipe $recipe, array $categoryIds): void
    {
        $recipe->categories()->sync($categoryIds);
    }

    /**
     * Create ingredients for the recipe
     *
     * For each ingredient:
     * - Look up existing ingredient by exact name match
     * - If not found, create new ingredient
     * - Look up or create unit
     * - Create the recipe-ingredient relationship
     */
    protected function createIngredients(Recipe $recipe, array $ingredientsData): void
    {
        $sortOrder = 1;

        foreach ($ingredientsData as $ingredientData) {
            // Find or create the ingredient by exact name match
            $ingredient = Ingredient::firstOrCreate(
                ['title' => trim($ingredientData['name'])],
                ['title' => trim($ingredientData['name'])]
            );

            // Find or create the unit
            $unit = $this->findOrCreateUnit($ingredientData['unit']);

            // Create the recipe-ingredient relationship
            RecipeIngredient::create([
                'recipe_id' => $recipe->id,
                'ingredient_id' => $ingredient->id,
                'unit_id' => $unit?->id,
                'quantity' => $ingredientData['quantity'],
                'sort_order' => $sortOrder,
                'note' => $ingredientData['note'] ?? null,
            ]);

            $sortOrder++;
        }
    }

    /**
     * Find or create a unit by title
     *
     * Returns null if unit string is empty.
     */
    protected function findOrCreateUnit(string $unitTitle): ?Unit
    {
        $unitTitle = trim($unitTitle);

        if (empty($unitTitle)) {
            return null;
        }

        return Unit::firstOrCreate(
            ['title' => $unitTitle],
            ['title' => $unitTitle]
        );
    }

    /**
     * Create steps for the recipe with auto-numbering
     */
    protected function createSteps(Recipe $recipe, array $stepsData): void
    {
        $stepNumber = 1;

        foreach ($stepsData as $stepData) {
            Step::create([
                'recipe_id' => $recipe->id,
                'step_number' => $stepNumber,
                'title' => $stepData['title'] ?? null,
                'description' => $stepData['description'],
            ]);

            $stepNumber++;
        }
    }

    /**
     * Upload images from base64 data and attach to recipe
     */
    protected function uploadImages(Recipe $recipe, array $imagesData): void
    {
        $sortOrder = 1;

        foreach ($imagesData as $imageData) {
            $file = $this->createFileFromBase64(
                $imageData['data'],
                $imageData['filename'],
                $imageData['content_type'] ?? null
            );

            if ($file) {
                $file->sort_order = $sortOrder;
                $file->save();

                $recipe->images()->add($file);
                $sortOrder++;
            }
        }
    }

    /**
     * Create a File model from base64 encoded data
     */
    protected function createFileFromBase64(
        string $base64Data,
        string $filename,
        ?string $contentType
    ): ?File {
        // Clean base64 data (remove data URI prefix if present)
        $base64Data = $this->cleanBase64Data($base64Data);

        // Decode base64
        $fileContent = base64_decode($base64Data, true);

        if ($fileContent === false) {
            return null;
        }

        // Detect content type if not provided
        if (!$contentType) {
            $contentType = RecipeStoreRequest::detectContentType($base64Data, $filename);
        }

        // Create temp file
        $tempPath = storage_path('temp/' . uniqid('upload_') . '_' . $filename);

        // Ensure temp directory exists
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        file_put_contents($tempPath, $fileContent);

        try {
            // Create File model
            $file = new File();
            $file->fromFile($tempPath);
            $file->file_name = $filename;
            $file->content_type = $contentType;
            $file->save();

            return $file;
        } finally {
            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    /**
     * Remove data URI prefix from base64 string
     */
    protected function cleanBase64Data(string $base64Data): string
    {
        // Remove data URI prefix like "data:image/jpeg;base64,"
        if (str_contains($base64Data, ',')) {
            $base64Data = explode(',', $base64Data, 2)[1];
        }

        // Remove any whitespace
        return preg_replace('/\s+/', '', $base64Data);
    }

    /**
     * Transform a Recipe model to API response format
     */
    public function transformRecipe(Recipe $recipe): array
    {
        return [
            'id' => $recipe->id,
            'title' => $recipe->title,
            'slug' => $recipe->slug,
            'subtitle' => $recipe->subtitle,
            'description' => $recipe->description,
            'tip' => $recipe->tip,
            'portions' => $recipe->portions,
            'difficulty' => $this->mapDifficultyToNumber($recipe->difficulty),
            'working_time_minutes' => $recipe->working_time_minutes,
            'cooking_time_minutes' => $recipe->cooking_time_minutes,
            'total_time_minutes' => $recipe->total_time,
            'is_published' => $recipe->is_published,
            'published_at' => $recipe->published_at?->toIso8601String(),
            'created_at' => $recipe->created_at?->toIso8601String(),
            'updated_at' => $recipe->updated_at?->toIso8601String(),
            'author' => $recipe->author ? [
                'id' => $recipe->author->id,
                'name' => $recipe->author->name,
            ] : null,
            'categories' => $recipe->categories->map(fn($cat) => [
                'id' => $cat->id,
                'title' => $cat->title,
                'slug' => $cat->slug,
            ])->toArray(),
            'ingredients' => $recipe->recipe_ingredients->map(fn($ri) => [
                'id' => $ri->ingredient->id,
                'name' => $ri->ingredient->title,
                'quantity' => $ri->quantity,
                'unit' => $ri->unit?->title,
                'note' => $ri->note,
            ])->toArray(),
            'steps' => $recipe->steps->map(fn($step) => [
                'number' => $step->step_number,
                'title' => $step->title,
                'description' => $step->description,
            ])->toArray(),
            'images' => $recipe->images->map(fn($img) => [
                'id' => $img->id,
                'filename' => $img->file_name,
                'url' => $img->getPath(),
                'thumb_url' => $img->getThumb(300, 300),
            ])->toArray(),
        ];
    }

    /**
     * Map difficulty string back to number
     */
    protected function mapDifficultyToNumber(string $difficulty): int
    {
        return match ($difficulty) {
            'easy' => 1,
            'medium' => 2,
            'hard' => 3,
            default => 2,
        };
    }
}
