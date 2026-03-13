<?php

namespace Ddk\Api\Http\Requests;

use Ddk\Recipe\Models\Category;

/**
 * Recipe Store Request Validator
 *
 * Validates incoming recipe creation requests with all required
 * fields, relationships, and image data.
 *
 * Expected JSON format:
 * {
 *   "title": "Spaghetti Carbonara",
 *   "subtitle": "Klassisch italienisch",
 *   "description": "Ein cremiges Pasta-Gericht...",
 *   "slug": "spaghetti-carbonara",  // optional, auto-generated from title
 *   "portions": 4,
 *   "difficulty": 2,  // 1=easy, 2=medium, 3=hard
 *   "working_time_minutes": 20,
 *   "cooking_time_minutes": 15,
 *   "author_id": 1,
 *   "published": true,  // optional, defaults to false
 *   "categories": [1, 2, 3],
 *   "ingredients": [
 *     {
 *       "name": "Spaghetti",
 *       "quantity": 500,
 *       "unit": "g",
 *       "note": "am besten Nr. 5"  // optional
 *     }
 *   ],
 *   "steps": [
 *     {
 *       "title": "Wasser kochen",  // optional
 *       "description": "Großen Topf mit Salzwasser..."
 *     }
 *   ],
 *   "images": [
 *     {
 *       "data": "base64-encoded-image-data",
 *       "filename": "carbonara.jpg",
 *       "content_type": "image/jpeg"  // optional, auto-detected
 *     }
 *   ]
 * }
 */
class RecipeStoreRequest extends BaseRequest
{
    /**
     * Map numeric difficulty to string
     */
    protected static array $difficultyMap = [
        1 => 'easy',
        2 => 'medium',
        3 => 'hard',
    ];

    /**
     * Validation rules
     */
    public function rules(): array
    {
        return [
            // Basic fields
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'tip' => 'nullable|string',
            'slug' => 'nullable|string|max:255',
            'portions' => 'nullable|integer|min:1',
            'difficulty' => 'required|integer|in:1,2,3',
            'working_time_minutes' => 'nullable|integer|min:0',
            'cooking_time_minutes' => 'nullable|integer|min:0',

            // Author
            'author_id' => 'required|integer|exists:users,id',

            // Publishing
            'published' => 'nullable|boolean',

            // Categories (array of IDs)
            'categories' => 'required|array|min:1',
            'categories.*' => 'integer|exists:ddk_recipe_categories,id',

            // Ingredients
            'ingredients' => 'required|array|min:1',
            'ingredients.*.name' => 'required|string|max:255',
            'ingredients.*.quantity' => 'required|numeric|min:0',
            'ingredients.*.unit' => 'required|string|max:50',
            'ingredients.*.note' => 'nullable|string|max:255',

            // Steps
            'steps' => 'required|array|min:1',
            'steps.*.title' => 'nullable|string|max:255',
            'steps.*.description' => 'required|string',

            // Images (base64 encoded)
            'images' => 'nullable|array',
            'images.*.data' => 'required_with:images|string',
            'images.*.filename' => 'required_with:images|string|max:255',
            'images.*.content_type' => 'nullable|string|in:image/jpeg,image/png,image/webp,image/jpg',
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'title.required' => trans('ddk.api::lang.validation.title_required'),
            'author_id.required' => trans('ddk.api::lang.validation.author_required'),
            'author_id.exists' => trans('ddk.api::lang.validation.author_required'),
            'difficulty.in' => trans('ddk.api::lang.validation.difficulty_invalid'),
            'ingredients.required' => trans('ddk.api::lang.validation.ingredients_required'),
            'ingredients.min' => trans('ddk.api::lang.validation.ingredients_required'),
            'steps.required' => trans('ddk.api::lang.validation.steps_required'),
            'steps.min' => trans('ddk.api::lang.validation.steps_required'),
            'categories.required' => trans('ddk.api::lang.validation.categories_required'),
            'categories.min' => trans('ddk.api::lang.validation.categories_required'),
            'images.*.content_type.in' => trans('ddk.api::lang.validation.image_invalid'),
        ];
    }

    /**
     * Custom attribute names for error messages
     */
    public function attributes(): array
    {
        return [
            'title' => 'Titel',
            'author_id' => 'Autor',
            'difficulty' => 'Schwierigkeit',
            'ingredients' => 'Zutaten',
            'steps' => 'Zubereitungsschritte',
            'categories' => 'Kategorien',
            'images' => 'Bilder',
        ];
    }

    /**
     * Convert difficulty number to string
     */
    public static function mapDifficulty(int $difficulty): string
    {
        return self::$difficultyMap[$difficulty] ?? 'medium';
    }

    /**
     * Get all allowed image content types
     */
    public static function getAllowedImageTypes(): array
    {
        return ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    }

    /**
     * Detect content type from base64 data or filename
     */
    public static function detectContentType(string $base64Data, string $filename): string
    {
        // Try to detect from base64 header
        if (str_starts_with($base64Data, '/9j/')) {
            return 'image/jpeg';
        }
        if (str_starts_with($base64Data, 'iVBORw')) {
            return 'image/png';
        }
        if (str_starts_with($base64Data, 'UklGR')) {
            return 'image/webp';
        }

        // Fallback to filename extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }
}
