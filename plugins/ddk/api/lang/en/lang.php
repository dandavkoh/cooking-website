<?php

return [
    'plugin' => [
        'name' => 'API',
        'description' => 'REST API for content management',
    ],
    'permissions' => [
        'tab' => 'API',
        'access_api' => 'Access API',
        'manage_recipes' => 'Manage recipes via API',
    ],
    'validation' => [
        'title_required' => 'The title is required.',
        'author_required' => 'The author is required.',
        'difficulty_invalid' => 'The difficulty must be easy, medium or hard.',
        'ingredients_required' => 'At least one ingredient is required.',
        'steps_required' => 'At least one step is required.',
        'categories_required' => 'At least one category is required.',
        'slug_unique' => 'This slug already exists.',
        'image_invalid' => 'Invalid image format. Allowed: jpg, jpeg, png, webp.',
    ],
    'errors' => [
        'unauthorized' => 'Unauthorized.',
        'forbidden' => 'Access denied.',
        'not_found' => 'Resource not found.',
        'validation_failed' => 'Validation failed.',
        'server_error' => 'A server error occurred.',
        'recipe_creation_failed' => 'Recipe could not be created.',
    ],
    'success' => [
        'recipe_created' => 'Recipe created successfully.',
        'recipe_updated' => 'Recipe updated successfully.',
        'recipe_deleted' => 'Recipe deleted successfully.',
    ],
];
