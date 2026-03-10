<?php

namespace Ddk\Api\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Ddk\Api\Classes\BaseApiController;
use Ddk\Api\Http\Requests\RecipeStoreRequest;
use Ddk\Api\Services\RecipeService;
use Ddk\Api\Exceptions\ApiException;
use Ddk\Api\Exceptions\ValidationException;
use Ddk\Api\Exceptions\NotFoundException;
use Ddk\Recipe\Models\Recipe;
use Exception;

/**
 * Recipe API Controller
 *
 * Handles all recipe-related API endpoints including creation,
 * retrieval, updating, and deletion of recipes.
 *
 * All endpoints require authentication via Bearer token.
 */
class RecipeController extends BaseApiController
{
    protected RecipeService $recipeService;

    public function __construct(RecipeService $recipeService)
    {
        $this->recipeService = $recipeService;
    }

    /**
     * List all recipes with pagination
     *
     * GET /api/v1/recipes
     *
     * Query parameters:
     * - page: Page number (default: 1)
     * - per_page: Items per page (default: 15, max: 100)
     * - published: Filter by published status (true/false)
     * - category_id: Filter by category ID
     * - search: Search in title
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->input('per_page', 15), 100);

            $query = Recipe::with([
                'categories',
                'recipe_ingredients.ingredient',
                'recipe_ingredients.unit',
                'steps',
                'images',
                'author',
            ]);

            // Filter by published status
            if ($request->has('published')) {
                $query->where('is_published', filter_var($request->input('published'), FILTER_VALIDATE_BOOLEAN));
            }

            // Filter by category
            if ($request->has('category_id')) {
                $query->whereHas('categories', function ($q) use ($request) {
                    $q->where('id', $request->input('category_id'));
                });
            }

            // Search by title
            if ($request->has('search')) {
                $query->search($request->input('search'));
            }

            // Order by creation date, newest first
            $query->orderBy('created_at', 'desc');

            $recipes = $query->paginate($perPage);

            return $this->paginated($recipes, fn($recipe) => $this->recipeService->transformRecipe($recipe));
        } catch (Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Get a single recipe by ID
     *
     * GET /api/v1/recipes/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $recipe = Recipe::with([
                'categories',
                'recipe_ingredients.ingredient',
                'recipe_ingredients.unit',
                'steps',
                'images',
                'author',
            ])->find($id);

            if (!$recipe) {
                return $this->notFound("Recipe with ID {$id} not found.");
            }

            return $this->success($this->recipeService->transformRecipe($recipe));
        } catch (Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Create a new recipe
     *
     * POST /api/v1/recipes
     *
     * Creates a complete recipe with all relationships in a single
     * transactional request.
     *
     * Required fields:
     * - title: Recipe title
     * - difficulty: 1=easy, 2=medium, 3=hard
     * - author_id: User ID of the author
     * - categories: Array of category IDs
     * - ingredients: Array of ingredient objects
     * - steps: Array of step objects
     *
     * Optional fields:
     * - slug: URL slug (auto-generated if not provided)
     * - subtitle: Recipe subtitle
     * - description: Full description
     * - portions: Number of portions
     * - working_time_minutes: Preparation time
     * - cooking_time_minutes: Cooking time
     * - published: Whether to publish immediately
     * - images: Array of base64 encoded images
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = new RecipeStoreRequest($request->all());
            $validatedData = $validator->validate();

            // Create recipe with all relationships
            $recipe = $this->recipeService->createRecipe($validatedData);

            return $this->created(
                $this->recipeService->transformRecipe($recipe),
                trans('ddk.api::lang.success.recipe_created')
            );
        } catch (ValidationException $e) {
            return $this->validationError($e->getErrors(), $e->getMessage());
        } catch (ApiException $e) {
            return $this->error($e->getMessage(), $e->getStatusCode(), $e->getErrors(), $e->getErrorCode());
        } catch (Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Update an existing recipe
     *
     * PUT /api/v1/recipes/{id}
     *
     * Updates a recipe and all its relationships. All fields are
     * optional - only provided fields will be updated.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $recipe = Recipe::find($id);

            if (!$recipe) {
                return $this->notFound("Recipe with ID {$id} not found.");
            }

            // TODO: Implement update logic similar to create
            // For now, return not implemented
            return $this->error('Update endpoint coming soon.', 501, [], 'NOT_IMPLEMENTED');
        } catch (Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Delete a recipe
     *
     * DELETE /api/v1/recipes/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $recipe = Recipe::find($id);

            if (!$recipe) {
                return $this->notFound("Recipe with ID {$id} not found.");
            }

            // Delete all related data
            $recipe->steps()->delete();
            $recipe->recipe_ingredients()->delete();
            $recipe->categories()->detach();
            $recipe->images()->delete();
            $recipe->delete();

            return $this->success(null, trans('ddk.api::lang.success.recipe_deleted'));
        } catch (Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
