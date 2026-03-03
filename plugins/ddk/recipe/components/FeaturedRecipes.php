<?php

namespace Ddk\Recipe\Components;

use Cms\Classes\ComponentBase;
use Ddk\Recipe\Models\Recipe;

class FeaturedRecipes extends ComponentBase
{
    /**
     * ============================================================
     * KONFIGURATION - Hier kannst du die Logik leicht anpassen:
     * ============================================================
     */
    
    /** Anzahl der Rezepte im Carousel */
    const LIMIT = 5;
    
    /** 
     * Sortierung: 'latest', 'oldest', 'random', 'fastest'
     * - latest: Neueste zuerst (nach published_at)
     * - oldest: Älteste zuerst
     * - random: Zufällige Auswahl
     * - fastest: Schnellste Rezepte zuerst
     */
    const SORT_BY = 'latest';

    public $recipes;

    public function componentDetails()
    {
        return [
            'name' => 'Featured Recipes',
            'description' => 'Zeigt empfohlene Rezepte im Carousel auf der Homepage'
        ];
    }

    public function onRun()
    {
        $this->recipes = $this->page['featuredRecipes'] = $this->loadRecipes();
    }

    /**
     * Lädt die Rezepte basierend auf der konfigurierten Logik.
     * 
     * Du kannst diese Methode erweitern um weitere Filter hinzuzufügen,
     * z.B. nur bestimmte Kategorien, nur mit Bildern, etc.
     */
    protected function loadRecipes()
    {
        $query = Recipe::query()
            ->where('is_published', true)
            ->with(['images', 'categories']);

        // Sortierung anwenden
        $this->applySorting($query);

        return $query->limit(self::LIMIT)->get();
    }

    /**
     * Wendet die Sortierung basierend auf SORT_BY an.
     * Hier kannst du weitere Sortieroptionen hinzufügen.
     */
    protected function applySorting($query)
    {
        switch (self::SORT_BY) {
            case 'latest':
                $query->orderBy('published_at', 'desc')
                      ->orderBy('created_at', 'desc');
                break;
                
            case 'oldest':
                $query->orderBy('published_at', 'asc')
                      ->orderBy('created_at', 'asc');
                break;
                
            case 'random':
                $query->inRandomOrder();
                break;
                
            case 'fastest':
                $query->orderByRaw('COALESCE(working_time_minutes, 0) + COALESCE(cooking_time_minutes, 0) ASC');
                break;
                
            default:
                $query->orderBy('created_at', 'desc');
        }
    }
}
