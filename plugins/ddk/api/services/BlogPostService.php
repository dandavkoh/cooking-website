<?php

namespace Ddk\Api\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use System\Models\File;
use Winter\Blog\Models\Post;
use Winter\Blog\Models\Category;
use Ddk\Api\Http\Requests\BlogPostStoreRequest;
use Ddk\Api\Exceptions\ApiException;
use Exception;
use Carbon\Carbon;

/**
 * Blog Post Service
 *
 * Handles all business logic for blog post creation and management
 * through the API. Ensures transactional integrity and proper
 * handling of all relationships including placeholder-based image uploads.
 */
class BlogPostService
{
    /**
     * Create a new blog post with all relationships
     *
     * @param array $data Validated request data
     * @return Post
     * @throws ApiException
     */
    public function createPost(array $data): Post
    {
        try {
            return DB::transaction(function () use ($data) {
                // 1. Generate slug
                $slug = $this->generateSlug($data['slug'] ?? null, $data['title']);

                // 2. Handle publishing
                $isPublished = $data['published'] ?? false;
                $publishedAt = $isPublished ? Carbon::now() : null;

                // 3. Extract and validate image placeholders BEFORE creating the post
                $imagePlaceholders = $this->extractImagePlaceholders($data['content']);
                $contentImagesMap = $this->buildContentImagesMap($data['content_images'] ?? []);
                $this->validateImagePlaceholders($imagePlaceholders, $contentImagesMap);

                // 4. Create the blog post (mass assignment not supported, set attributes individually)
                $post = new Post();
                $post->title = $data['title'];
                $post->slug = $slug;
                $post->content = $data['content']; // Original content with placeholders
                $post->excerpt = $data['excerpt'] ?? null;
                $post->user_id = $data['author_id'];
                $post->published = $isPublished;
                $post->published_at = $publishedAt;
                $post->save();

                // 5. Attach categories
                if (!empty($data['categories'])) {
                    $post->categories()->attach($data['categories']);
                }

                // 6. Handle featured images
                if (!empty($data['featured_images'])) {
                    $this->uploadFeaturedImages($post, $data['featured_images']);
                }

                // 7. Handle content images and replace placeholders
                if (!empty($data['content_images'])) {
                    $imageReplacements = $this->processContentImages($post, $data['content_images']);
                    $post->content = $this->replaceImagePlaceholders($post->content, $imageReplacements);
                    $post->save();
                }

                // 8. Reload post with all relationships
                return $post->fresh(['categories', 'featured_images', 'content_images', 'user']);
            });
        } catch (Exception $e) {
            throw new ApiException(
                trans('ddk.api::lang.errors.blog_post_creation_failed') . ': ' . $e->getMessage(),
                500,
                'BLOG_POST_CREATION_FAILED'
            );
        }
    }

    /**
     * Generate slug from title
     */
    protected function generateSlug(?string $providedSlug, string $title): string
    {
        return $providedSlug ? Str::slug($providedSlug) : Str::slug($title);
    }

    /**
     * Extract all image placeholders from content
     * Format: {{IMAGE:placeholder}}
     *
     * @return array Array of placeholder names
     */
    protected function extractImagePlaceholders(string $content): array
    {
        $matches = [];
        $pattern = '/\{\{IMAGE:([^}]+)\}\}/';

        if (preg_match_all($pattern, $content, $matches)) {
            return $matches[1]; // Returns array of placeholder names
        }

        return [];
    }

    /**
     * Build a map of placeholder => image data from content_images array
     */
    protected function buildContentImagesMap(array $contentImages): array
    {
        $map = [];

        foreach ($contentImages as $imageData) {
            if (isset($imageData['placeholder'])) {
                $map[$imageData['placeholder']] = $imageData;
            }
        }

        return $map;
    }

    /**
     * Validate that all placeholders have corresponding image uploads
     *
     * @throws ApiException
     */
    protected function validateImagePlaceholders(array $placeholders, array $contentImagesMap): void
    {
        foreach ($placeholders as $placeholder) {
            if (!isset($contentImagesMap[$placeholder])) {
                throw new ApiException(
                    "Image placeholder {{IMAGE:{$placeholder}}} found in content but no corresponding image provided in content_images array",
                    422,
                    'MISSING_IMAGE_FOR_PLACEHOLDER'
                );
            }
        }
    }

    /**
     * Upload and process content images, building replacement map
     *
     * @return array Map of placeholder => markdown
     */
    protected function processContentImages(Post $post, array $imagesData): array
    {
        $replacements = [];

        foreach ($imagesData as $imageData) {
            if (!isset($imageData['placeholder'])) {
                continue;
            }

            $file = $this->uploadImage($imageData);

            if ($file) {
                // Attach to post's content_images relationship
                $post->content_images()->add($file);

                // Build replacement: {{IMAGE:placeholder}} => ![filename](url)
                $placeholder = $imageData['placeholder'];
                $markdown = sprintf('![%s](%s)', $imageData['filename'], $file->getPath());
                $replacements["{{IMAGE:{$placeholder}}}"] = $markdown;
            }
        }

        return $replacements;
    }

    /**
     * Replace all placeholders in content with markdown image tags
     */
    protected function replaceImagePlaceholders(string $content, array $replacements): string
    {
        foreach ($replacements as $placeholder => $markdown) {
            // Replace all occurrences of this placeholder
            $content = str_replace($placeholder, $markdown, $content);
        }

        return $content;
    }

    /**
     * Upload featured images and attach to post
     */
    protected function uploadFeaturedImages(Post $post, array $imagesData): void
    {
        $sortOrder = 1;

        foreach ($imagesData as $imageData) {
            $file = $this->uploadImage($imageData);

            if ($file) {
                $file->sort_order = $sortOrder;
                $file->save();

                $post->featured_images()->add($file);
                $sortOrder++;
            }
        }
    }

    /**
     * Upload and create File model from base64 data
     */
    protected function uploadImage(array $imageData): ?File
    {
        if (!isset($imageData['data']) || !isset($imageData['filename'])) {
            return null;
        }

        $base64Data = $this->cleanBase64Data($imageData['data']);
        $fileContent = base64_decode($base64Data, true);

        if ($fileContent === false) {
            return null;
        }

        // Detect content type
        $contentType = $imageData['content_type'] ?? $this->detectContentType($base64Data, $imageData['filename']);

        // Create temp file
        $tempPath = storage_path('temp/' . uniqid('upload_') . '_' . $imageData['filename']);

        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        file_put_contents($tempPath, $fileContent);

        try {
            $file = new File();
            $file->fromFile($tempPath);
            $file->file_name = $imageData['filename'];
            $file->content_type = $contentType;
            $file->save();

            return $file;
        } finally {
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
        }
    }

    /**
     * Clean base64 data (remove data URI prefix)
     */
    protected function cleanBase64Data(string $base64Data): string
    {
        if (str_contains($base64Data, ',')) {
            $base64Data = explode(',', $base64Data, 2)[1];
        }

        return preg_replace('/\s+/', '', $base64Data);
    }

    /**
     * Detect content type from base64 data or filename
     */
    protected function detectContentType(string $base64Data, string $filename): string
    {
        if (str_starts_with($base64Data, '/9j/')) {
            return 'image/jpeg';
        }
        if (str_starts_with($base64Data, 'iVBORw')) {
            return 'image/png';
        }
        if (str_starts_with($base64Data, 'UklGR')) {
            return 'image/webp';
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    /**
     * Transform a Post model to API response format
     */
    public function transformPost(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'content' => $post->content,
            'content_html' => $post->content_html,
            'excerpt' => $post->excerpt,
            'published' => $post->published,
            'published_at' => $post->published_at?->toIso8601String(),
            'created_at' => $post->created_at?->toIso8601String(),
            'updated_at' => $post->updated_at?->toIso8601String(),
            'author' => $post->user ? [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'email' => $post->user->email,
            ] : null,
            'categories' => $post->categories->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
            ])->toArray(),
            'featured_images' => $post->featured_images->map(fn($img) => [
                'id' => $img->id,
                'filename' => $img->file_name,
                'url' => $img->getPath(),
                'thumb_url' => $img->getThumb(300, 300),
            ])->toArray(),
            'content_images' => $post->content_images->map(fn($img) => [
                'id' => $img->id,
                'filename' => $img->file_name,
                'url' => $img->getPath(),
                'thumb_url' => $img->getThumb(300, 300),
            ])->toArray(),
        ];
    }
}
