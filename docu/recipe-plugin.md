# Recipe Plugin (ddk.recipe)

Dokumentation für das Recipe Plugin der Cooking Website.

---

## Übersicht

Das Recipe Plugin verwaltet Rezepte mit folgenden Features:
- Rezepte mit Titel, Beschreibung, Schwierigkeit, Arbeits- und Kochzeit
- Zutaten mit Mengenangaben und Einheiten
- Zubereitungsschritte
- Kategorien
- Bildergalerie
- Autoren-Zuweisung
- Veröffentlichungsstatus mit zeitgesteuerter Publikation

---

## Datenbank-Struktur

### Tabellen

| Tabelle | Beschreibung |
|---------|--------------|
| `ddk_recipes` | Haupttabelle für Rezepte |
| `ddk_recipe_ingredients` | Zutaten |
| `ddk_recipe_units` | Einheiten (g, ml, Stück, etc.) |
| `ddk_recipe_categories` | Kategorien (hierarchisch) |
| `ddk_recipe_steps` | Zubereitungsschritte |
| `ddk_recipe_ingredients_map` | Pivot: Rezept ↔ Zutaten |
| `ddk_recipe_categories_map` | Pivot: Rezept ↔ Kategorien |

---

## User-Gruppen Konfiguration

### Voraussetzung: UserGroup "recipe"

Damit Autoren den Rezepten zugewiesen werden können, muss eine UserGroup mit dem Code `recipe` existieren.

#### Einrichtung:

1. **Backend öffnen:** `/backend/winter/user/usergroups`
2. **Neue Gruppe erstellen:**
   - **Name:** `Recipe Authors` (oder beliebig)
   - **Code:** `recipe` ← **Wichtig: muss exakt so lauten!**
   - **Description:** Benutzer, die als Rezept-Autoren ausgewählt werden können
3. **Speichern**

#### User zur Gruppe hinzufügen:

1. **Backend öffnen:** `/backend/winter/user/users`
2. **Benutzer bearbeiten**
3. **Tab "Groups"** → Gruppe `recipe` aktivieren
4. **Speichern**

> **Hinweis:** Nur User, die der Gruppe `recipe` zugeordnet sind, erscheinen im Autor-Dropdown bei der Rezept-Erstellung.

---

## Backend-Zugriff

### Navigation

Im Backend unter **Rezepte** (Icon: Besteck) gibt es folgende Unterpunkte:

| Menüpunkt | Pfad | Berechtigungen |
|-----------|------|----------------|
| Rezepte | `/backend/ddk/recipe/recipes` | `ddk.recipe.access_recipes` |
| Zutaten | `/backend/ddk/recipe/ingredients` | `ddk.recipe.access_ingredients` |
| Einheiten | `/backend/ddk/recipe/units` | `ddk.recipe.access_units` |
| Kategorien | `/backend/ddk/recipe/categories` | `ddk.recipe.access_categories` |

### Berechtigungen

| Berechtigung | Beschreibung |
|--------------|--------------|
| `ddk.recipe.access_recipes` | Rezepte verwalten |
| `ddk.recipe.access_ingredients` | Zutaten verwalten |
| `ddk.recipe.access_units` | Einheiten verwalten |
| `ddk.recipe.access_categories` | Kategorien verwalten |

---

## Frontend-Komponenten

Das Plugin stellt folgende CMS-Komponenten bereit:

| Komponente | Alias | Beschreibung |
|------------|-------|--------------|
| `CategoryFilter` | `categoryFilter` | Kategorie-Filter für Rezeptlisten |
| `RecipeDetail` | `recipeDetail` | Einzelne Rezeptansicht |
| `RecipeList` | `recipeList` | Rezeptliste mit Filterung |
| `FeaturedRecipes` | `featuredRecipes` | Hervorgehobene Rezepte |

### Beispiel: Rezeptliste einbinden

```twig
[recipeList]
==
{% component 'recipeList' %}
```

---

## Rezept-Model

### Felder

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `title` | string | Rezepttitel (erforderlich) |
| `slug` | string | URL-Slug (auto-generiert) |
| `subtitle` | string | Untertitel |
| `description` | text | Beschreibung (Rich-Editor) |
| `portions` | integer | Anzahl Portionen |
| `difficulty` | enum | `easy`, `medium`, `hard` |
| `working_time_minutes` | integer | Arbeitszeit in Minuten |
| `cooking_time_minutes` | integer | Kochzeit in Minuten |
| `author_id` | integer | FK zu `users` Tabelle |
| `is_published` | boolean | Veröffentlichungsstatus |
| `published_at` | datetime | Zeitgesteuerte Publikation |

### Relationen

| Relation | Typ | Ziel |
|----------|-----|------|
| `author` | belongsTo | `Winter\User\Models\User` |
| `categories` | belongsToMany | `Category` |
| `ingredients` | belongsToMany | `Ingredient` (mit Pivot) |
| `steps` | hasMany | `Step` |
| `images` | attachMany | `System\Models\File` |

### Scopes

```php
// Nur veröffentlichte Rezepte
Recipe::isPublished()->get();

// Fuzzy-Suche mit SOUNDEX
Recipe::search('Spagetti')->get(); // findet auch "Spaghetti"
```

### Computed Attributes

```php
$recipe->total_time; // working_time + cooking_time
```

---

## Schwierigkeitsgrade

| Wert | Deutsch | Englisch |
|------|---------|----------|
| `easy` | Einfach | Easy |
| `medium` | Mittel | Medium |
| `hard` | Schwer | Hard |

---

## Dateipfade

```
plugins/ddk/recipe/
├── Plugin.php              # Plugin-Definition
├── components/             # Frontend-Komponenten
│   ├── CategoryFilter.php
│   ├── FeaturedRecipes.php
│   ├── RecipeDetail.php
│   └── RecipeList.php
├── controllers/            # Backend-Controller
├── models/
│   ├── Recipe.php
│   ├── Ingredient.php
│   ├── Unit.php
│   ├── Category.php
│   ├── Step.php
│   └── RecipeIngredient.php
├── lang/
│   ├── de/lang.php         # Deutsche Übersetzungen
│   └── en/lang.php         # Englische Übersetzungen
└── updates/
    └── v1.0.0/
        └── create_tables.php
```

---

## Abhängigkeiten

- **Winter.User Plugin** - Für die Autoren-Verwaltung (`author_id` → `users` Tabelle)
