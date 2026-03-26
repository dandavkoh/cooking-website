<?php

namespace Ddk\Api\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Ddk\Api\Classes\BaseApiController;
use Ddk\Api\Http\Requests\BlogPostStoreRequest;
use Ddk\Api\Services\BlogPostService;
use Ddk\Api\Exceptions\ApiException;
use Ddk\Api\Exceptions\ValidationException;
use Winter\Blog\Models\Post;
use Exception;

/**
 * Blog Post API Controller
 *
 * Handles all blog post-related API endpoints including creation,
 * retrieval, updating, and deletion with full placeholder image support.
 * All endpoints require authentication via Bearer token.
 */
class BlogPostController extends BaseApiController
{
    protected BlogPostService $blogPostService;

    public function __construct(BlogPostService $blogPostService)
    {
        $this->blogPostService = $blogPostService;
    }

    /**
     * List all blog posts with pagination
     *
     * GET /api/v1/posts
     *
     * Query parameters:
     * - page: Page number (default: 1)
     * - per_page: Items per page (default: 15, max: 100)
     * - published: Filter by published status (true/false)
     * - category_id: Filter by category ID
     * - search: Search in title or content
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = min($request->input('per_page', 15), 100);

            $query = Post::with([
                'categories',
                'featured_images',
                'content_images',
                'author',
            ]);

            // Filter by published status
            if ($request->has('published')) {
                $query->where('published', filter_var($request->input('published'), FILTER_VALIDATE_BOOLEAN));
            }

            // Filter by category
            if ($request->has('category_id')) {
                $query->whereHas('categories', function ($q) use ($request) {
                    $q->where('id', $request->input('category_id'));
                });
            }

            // Search by title or content
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                });
            }

            // Order by creation date, newest first
            $query->orderBy('created_at', 'desc');

            $posts = $query->paginate($perPage);

            return $this->paginated($posts, fn($post) => $this->blogPostService->transformPost($post));
        } catch (Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Get a single blog post by ID
     *
     * GET /api/v1/posts/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $post = Post::with([
                'categories',
                'featured_images',
                'content_images',
                'author',
            ])->find($id);

            if (!$post) {
                return $this->notFound("Blog post with ID {$id} not found.");
            }

            return $this->success($this->blogPostService->transformPost($post));
        } catch (Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Create a new blog post
     *
     * POST /api/v1/posts
     *
     * Creates a complete blog post with all relationships including
     * placeholder-based image uploads.
     *
     * Required fields:
     * - title: Blog post title
     * - content: Blog post content with {{IMAGE:placeholder}} markers
     * - author_id: User ID of the author
     *
     * Optional fields:
     * - slug: URL slug (auto-generated if not provided)
     * - excerpt: Brief summary
     * - published: Whether to publish immediately (default: false)
     * - categories: Array of category IDs
     * - featured_images: Array of base64 encoded images
     * - content_images: Array of images with placeholders for content
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = new BlogPostStoreRequest($request->all());
            $validatedData = $validator->validate();

            // Create blog post with all relationships
            $post = $this->blogPostService->createPost($validatedData);

            return $this->created(
                $this->blogPostService->transformPost($post),
                trans('ddk.api::lang.success.post_created')
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
     * Update an existing blog post
     *
     * PUT /api/v1/posts/{id}
     *
     * Updates a blog post and all its relationships. All fields are
     * optional - only provided fields will be updated.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $post = Post::find($id);

            if (!$post) {
                return $this->notFound("Blog post with ID {$id} not found.");
            }

            // TODO: Implement update logic similar to create
            // For now, return not implemented
            return $this->error('Update endpoint coming soon.', 501, [], 'NOT_IMPLEMENTED');
        } catch (Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Delete a blog post
     *
     * DELETE /api/v1/posts/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $post = Post::find($id);

            if (!$post) {
                return $this->notFound("Blog post with ID {$id} not found.");
            }

            // Delete all related data
            $post->categories()->detach();
            $post->featured_images()->delete();
            $post->content_images()->delete();
            $post->delete();

            return $this->success(null, trans('ddk.api::lang.success.post_deleted'));
        } catch (Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
