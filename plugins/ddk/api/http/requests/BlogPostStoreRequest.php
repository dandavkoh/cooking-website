<?php

namespace Ddk\Api\Http\Requests;

use Winter\Blog\Models\Category;

/**
 * Blog Post Store Request Validator
 *
 * Validates incoming blog post creation requests with all required
 * fields, relationships, and image data.
 *
 * Expected JSON format:
 * {
 * "title": "My First Blog Post",
 * "slug": "my-first-blog-post", // optional, auto-generated from title
 * "content": "This is the blog post content with {{IMAGE:image1.jpg}} placeholders",
 * "excerpt": "Brief summary of the post",
 * "published": true, // optional, defaults to false
 * "author_id": 1,
 * "categories": [1, 2],
 * "featured_images": [
 * {
 * "data": "base64-encoded-image-data",
 * "filename": "featured.jpg",
 * "content_type": "image/jpeg" // optional
 * }
 * ],
 * "content_images": [
 * {
 * "data": "base64-encoded-image-data",
 * "filename": "image1.jpg",
 * "placeholder": "image1.jpg", // must match the placeholder in content
 * "content_type": "image/jpeg" // optional
 * }
 * ]
 * }
 */
class BlogPostStoreRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            // Basic fields
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            
            // Author
            'author_id' => 'required|integer|exists:users,id',
            
            // Publishing
            'published' => 'nullable|boolean',
            
            // Categories (array of winter_blog_categories IDs)
            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:winter_blog_categories,id',
            
            // Featured images
            'featured_images' => 'nullable|array',
            'featured_images.*.data' => 'required_with:featured_images|string',
            'featured_images.*.filename' => 'required_with:featured_images|string|max:255',
            'featured_images.*.content_type' => 'nullable|string|in:image/jpeg,image/png,image/webp,image/jpg',
            
            // Content images (to replace placeholders in content)
            'content_images' => 'nullable|array',
            'content_images.*.data' => 'required_with:content_images|string',
            'content_images.*.filename' => 'required_with:content_images|string|max:255',
            'content_images.*.placeholder' => 'required_with:content_images|string|max:255',
            'content_images.*.content_type' => 'nullable|string|in:image/jpeg,image/png,image/webp,image/jpg',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Titel ist erforderlich',
            'author_id.required' => 'Autor-ID ist erforderlich',
            'author_id.exists' => 'Autor existiert nicht',
            'content.required' => 'Inhalt ist erforderlich',
            'images.*.content_type.in' => 'Bildformat muss JPEG, PNG oder WebP sein',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Titel',
            'author_id' => 'Autor',
            'content' => 'Inhalt',
            'excerpt' => 'Auszug',
            'categories' => 'Kategorien',
            'featured_images' => 'Hauptbilder',
            'content_images' => 'Inhaltsbilder',
        ];
    }
}
