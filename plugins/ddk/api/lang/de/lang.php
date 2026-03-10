<?php

return [
    'plugin' => [
        'name' => 'API',
        'description' => 'REST API für die Verwaltung von Inhalten',
    ],
    'permissions' => [
        'tab' => 'API',
        'access_api' => 'Zugriff auf API',
        'manage_recipes' => 'Rezepte über API verwalten',
    ],
    'validation' => [
        'title_required' => 'Der Titel ist erforderlich.',
        'author_required' => 'Der Autor ist erforderlich.',
        'difficulty_invalid' => 'Die Schwierigkeit muss easy, medium oder hard sein.',
        'ingredients_required' => 'Mindestens eine Zutat ist erforderlich.',
        'steps_required' => 'Mindestens ein Zubereitungsschritt ist erforderlich.',
        'categories_required' => 'Mindestens eine Kategorie ist erforderlich.',
        'slug_unique' => 'Dieser Slug existiert bereits.',
        'image_invalid' => 'Das Bildformat ist ungültig. Erlaubt sind: jpg, jpeg, png, webp.',
    ],
    'errors' => [
        'unauthorized' => 'Nicht autorisiert.',
        'forbidden' => 'Zugriff verweigert.',
        'not_found' => 'Ressource nicht gefunden.',
        'validation_failed' => 'Validierung fehlgeschlagen.',
        'server_error' => 'Ein Serverfehler ist aufgetreten.',
        'recipe_creation_failed' => 'Rezept konnte nicht erstellt werden.',
    ],
    'success' => [
        'recipe_created' => 'Rezept erfolgreich erstellt.',
        'recipe_updated' => 'Rezept erfolgreich aktualisiert.',
        'recipe_deleted' => 'Rezept erfolgreich gelöscht.',
    ],
];
