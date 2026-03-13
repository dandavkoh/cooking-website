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



--
-- Daten für Tabelle `ddk_recipe_categories`
--

INSERT INTO `ddk_recipe_categories` (`id`, `title`, `slug`, `parent_id`, `nest_left`, `nest_right`, `nest_depth`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Backen & Süßspeisen', 'backen-suessspeisen', NULL, 1, 18, 0, 0, '2026-03-03 08:30:20', '2026-03-03 18:58:15'),
(2, 'Cremes', 'cremes', 1, 2, 3, 1, 1, '2026-03-03 08:31:45', '2026-03-03 09:38:56'),
(3, 'Brot und Brötchen', 'brot-und-brotchen', 1, 4, 5, 1, 0, '2026-03-03 08:32:20', '2026-03-03 09:38:18'),
(4, 'Eis', 'eis', 1, 6, 7, 1, 2, '2026-03-03 09:39:35', '2026-03-03 09:39:35'),
(5, 'Kekse & Plätzchen', 'kekse-platzchen', 1, 8, 9, 1, 3, '2026-03-03 09:40:04', '2026-03-03 09:40:28'),
(6, 'Konfiserie', 'konfiserie', 1, 10, 11, 1, 4, '2026-03-03 09:40:45', '2026-03-03 09:40:45'),
(7, 'Kuchen', 'kuchen', 1, 12, 13, 1, 5, '2026-03-03 09:40:56', '2026-03-03 09:40:56'),
(8, 'Tarte/Quiche', 'tartequiche', 1, 14, 15, 1, 6, '2026-03-03 09:41:14', '2026-03-03 09:41:14'),
(9, 'Torten', 'torten', 1, 16, 17, 1, 7, '2026-03-03 09:41:38', '2026-03-03 09:41:38'),
(10, 'Zubereitungsarten', 'zubereitungsarten', NULL, 19, 94, 0, 1, '2026-03-03 09:46:59', '2026-03-03 09:57:14'),
(11, 'Aufstrich', 'aufstrich', 10, 20, 21, 1, 0, '2026-03-03 09:47:17', '2026-03-03 09:47:17'),
(12, 'Backen', 'backen', 10, 22, 23, 1, 1, '2026-03-03 09:49:21', '2026-03-03 09:49:21'),
(13, 'Beilage', 'beilage-zubereitungsarten', 10, 24, 25, 1, 2, '2026-03-03 09:49:36', '2026-03-13 08:12:52'),
(14, 'Brotspeise', 'brotspeise', 10, 26, 27, 1, 3, '2026-03-03 09:49:52', '2026-03-03 09:49:52'),
(15, 'Eierspeise', 'eierspeise', 10, 28, 29, 1, 4, '2026-03-03 09:50:16', '2026-03-03 09:50:16'),
(16, 'Eintopf', 'eintopf-zubereitungsarten', 10, 30, 31, 1, 5, '2026-03-03 09:50:29', '2026-03-13 08:17:49'),
(17, 'Fisch', 'fisch-zubereitungsarten', 10, 32, 33, 1, 6, '2026-03-03 09:50:40', '2026-03-13 08:18:37'),
(18, 'Frucht', 'frucht', 10, 34, 35, 1, 7, '2026-03-03 09:50:53', '2026-03-03 09:50:53'),
(19, 'Frühstück', 'fruhstuck-zubereitungsarten', 10, 36, 37, 1, 8, '2026-03-03 09:51:04', '2026-03-13 08:16:40'),
(20, 'Gemüse', 'gemuse-zubereitungsarten', 10, 38, 39, 1, 9, '2026-03-03 09:51:17', '2026-03-13 08:15:34'),
(21, 'Gewürze/Öl/Essig/Pasten', 'gewurzeolessigpasten', 10, 40, 41, 1, 10, '2026-03-03 09:51:39', '2026-03-03 09:51:39'),
(22, 'Grundrezepte', 'grundrezepte', 10, 42, 43, 1, 11, '2026-03-03 09:51:56', '2026-03-03 09:51:56'),
(23, 'Haltbarmachen', 'haltbarmachen', 10, 44, 45, 1, 12, '2026-03-03 09:52:09', '2026-03-03 09:52:09'),
(24, 'Krustentier & Muscheln', 'krustentier-muscheln-zubereitungsarten', 10, 46, 47, 1, 13, '2026-03-03 09:52:26', '2026-03-13 08:19:54'),
(25, 'Käse', 'kase', 10, 48, 49, 1, 14, '2026-03-03 09:52:37', '2026-03-03 09:52:37'),
(26, 'Mehlspeisen', 'mehlspeisen', 10, 50, 51, 1, 15, '2026-03-03 09:52:48', '2026-03-03 09:52:48'),
(27, 'Methoden', 'methoden', 10, 52, 85, 1, 16, '2026-03-03 09:53:04', '2026-03-03 09:56:28'),
(28, 'Auflauf', 'auflauf', 27, 53, 54, 2, 0, '2026-03-03 09:53:15', '2026-03-03 09:53:15'),
(29, 'Barbecue & Grill', 'barbecue-grill', 27, 55, 56, 2, 1, '2026-03-03 09:53:30', '2026-03-03 09:53:30'),
(30, 'Blanchieren', 'blanchieren', 27, 57, 58, 2, 2, '2026-03-03 09:53:45', '2026-03-03 09:53:45'),
(31, 'Braten', 'braten', 27, 59, 60, 2, 3, '2026-03-03 09:53:57', '2026-03-03 09:54:11'),
(32, 'Dünsten', 'dunsten', 27, 61, 62, 2, 4, '2026-03-03 09:54:26', '2026-03-03 09:54:26'),
(33, 'Flambieren', 'flambieren', 27, 63, 64, 2, 5, '2026-03-03 09:54:36', '2026-03-03 09:54:36'),
(34, 'Fondue', 'fondue', 27, 65, 66, 2, 6, '2026-03-03 09:54:48', '2026-03-03 09:54:48'),
(35, 'Frittieren', 'frittieren', 27, 67, 68, 2, 7, '2026-03-03 09:55:00', '2026-03-03 09:55:00'),
(36, 'Kochen', 'kochen', 27, 69, 70, 2, 8, '2026-03-03 09:55:10', '2026-03-03 09:55:10'),
(37, 'Marinieren', 'marinieren', 27, 71, 72, 2, 9, '2026-03-03 09:55:21', '2026-03-03 09:55:21'),
(38, 'Mikrowelle', 'mikrowelle', 27, 73, 74, 2, 10, '2026-03-03 09:55:35', '2026-03-03 09:55:35'),
(39, 'Molekularküche', 'molekularkuche', 27, 75, 76, 2, 11, '2026-03-03 09:55:43', '2026-03-03 09:55:43'),
(40, 'Römertopf', 'romertopf', 27, 77, 78, 2, 12, '2026-03-03 09:55:53', '2026-03-03 09:55:53'),
(41, 'Schmoren', 'schmoren', 27, 79, 80, 2, 13, '2026-03-03 09:56:06', '2026-03-03 09:56:06'),
(42, 'Wok', 'wok', 27, 81, 82, 2, 14, '2026-03-03 09:56:19', '2026-03-03 09:56:19'),
(43, 'Überbacken', 'uberbacken', 27, 83, 84, 2, 15, '2026-03-03 09:56:28', '2026-03-03 09:56:28'),
(44, 'Pasta', 'pasta', 10, 86, 87, 1, 17, '2026-03-03 09:56:38', '2026-03-03 09:56:38'),
(45, 'Saucen', 'saucen', 10, 88, 89, 1, 18, '2026-03-03 09:56:49', '2026-03-03 09:56:49'),
(46, 'Snacks und kleine Gerichte', 'snacks-und-kleine-gerichte', 10, 90, 91, 1, 19, '2026-03-03 09:57:02', '2026-03-03 09:57:02'),
(47, 'Süßspeisen', 'sussspeisen', 10, 92, 93, 1, 20, '2026-03-03 09:57:14', '2026-03-03 09:57:14'),
(48, 'Saisonal', 'saisonal', NULL, 95, 112, 0, 2, '2026-03-13 07:46:26', '2026-03-13 07:49:22'),
(49, 'Frühling', 'fruhling', 48, 96, 97, 1, 0, '2026-03-13 07:46:44', '2026-03-13 07:46:44'),
(50, 'Halloween', 'halloween', 48, 98, 99, 1, 1, '2026-03-13 07:46:57', '2026-03-13 07:46:57'),
(51, 'Herbst', 'herbst', 48, 100, 101, 1, 2, '2026-03-13 07:47:22', '2026-03-13 07:47:22'),
(52, 'Ostern', 'ostern', 48, 102, 103, 1, 3, '2026-03-13 07:47:58', '2026-03-13 07:47:58'),
(53, 'Silvester', 'silvester', 48, 104, 105, 1, 4, '2026-03-13 07:48:18', '2026-03-13 07:48:18'),
(54, 'Sommer', 'sommer', 48, 106, 107, 1, 5, '2026-03-13 07:48:29', '2026-03-13 07:48:30'),
(55, 'Weihnachten', 'weihnachten', 48, 108, 109, 1, 6, '2026-03-13 07:48:43', '2026-03-13 07:48:43'),
(56, 'Winter', 'winter', 48, 110, 111, 1, 7, '2026-03-13 07:49:22', '2026-03-13 07:49:22'),
(57, 'Spezielles', 'spezielles', NULL, 113, 160, 0, 3, '2026-03-13 07:50:10', '2026-03-13 07:58:23'),
(58, 'Babynahrung', 'babynahrung', 57, 114, 115, 1, 0, '2026-03-13 07:50:46', '2026-03-13 07:50:46'),
(59, 'Camping', 'camping', 57, 116, 117, 1, 1, '2026-03-13 07:51:00', '2026-03-13 07:51:00'),
(60, 'cross-cooking', 'cross-cooking', 57, 118, 119, 1, 2, '2026-03-13 07:51:31', '2026-03-13 07:51:31'),
(61, 'Ernährungskonzepte', 'ernahrungskonzepte', 57, 120, 137, 1, 3, '2026-03-13 07:51:50', '2026-03-13 07:53:53'),
(62, 'Diabetiker', 'diabetiker', 61, 121, 122, 2, 0, '2026-03-13 07:52:05', '2026-03-13 07:52:05'),
(63, 'Fettarm', 'fettarm', 61, 123, 124, 2, 1, '2026-03-13 07:52:18', '2026-03-13 07:52:18'),
(64, 'Kalorienarm', 'kalorienarm', 61, 125, 126, 2, 2, '2026-03-13 07:52:31', '2026-03-13 07:52:31'),
(65, 'Low fat', 'low-fat', 61, 127, 128, 2, 3, '2026-03-13 07:53:01', '2026-03-13 07:53:01'),
(66, 'Trennkost', 'trennkost', 61, 129, 130, 2, 4, '2026-03-13 07:53:12', '2026-03-13 07:53:12'),
(67, 'Vegan', 'vegan', 61, 131, 132, 2, 5, '2026-03-13 07:53:23', '2026-03-13 07:53:23'),
(68, 'Vegetarisch', 'vegetarisch', 61, 133, 134, 2, 6, '2026-03-13 07:53:33', '2026-03-13 07:53:33'),
(69, 'Vollwert', 'vollwert', 61, 135, 136, 2, 7, '2026-03-13 07:53:53', '2026-03-13 07:53:53'),
(70, 'Festlich', 'festlich', 57, 138, 139, 1, 4, '2026-03-13 07:54:16', '2026-03-13 07:54:16'),
(71, 'Fingerfood', 'fingerfood', 57, 140, 141, 1, 5, '2026-03-13 07:54:37', '2026-03-13 07:54:37'),
(72, 'Geheimrezepte', 'geheimrezepte', 57, 142, 143, 1, 6, '2026-03-13 07:55:17', '2026-03-13 07:55:17'),
(73, 'Kinder', 'kinder', 57, 144, 145, 1, 7, '2026-03-13 07:55:51', '2026-03-13 07:55:51'),
(74, 'Party', 'party', 57, 146, 147, 1, 8, '2026-03-13 07:56:00', '2026-03-13 07:56:00'),
(75, 'Raffiniert & preiswert', 'raffiniert-preiswert', 57, 148, 149, 1, 9, '2026-03-13 07:56:14', '2026-03-13 07:56:14'),
(76, 'Resteverwertung', 'resteverwertung', 57, 150, 151, 1, 10, '2026-03-13 07:56:30', '2026-03-13 07:56:30'),
(77, 'Saucen & Dips', 'saucen-dips', 57, 152, 153, 1, 11, '2026-03-13 07:57:36', '2026-03-13 07:57:36'),
(78, 'Schnell und einfach', 'schnell-und-einfach', 57, 154, 155, 1, 12, '2026-03-13 07:57:50', '2026-03-13 07:57:50'),
(79, 'Studentenküche', 'studentenkuche', 57, 156, 157, 1, 13, '2026-03-13 07:58:01', '2026-03-13 07:58:01'),
(80, 'Wursten', 'wursten', 57, 158, 159, 1, 14, '2026-03-13 07:58:23', '2026-03-13 07:58:23'),
(81, 'Regional', 'regional', NULL, 161, 248, 0, 4, '2026-03-13 07:58:40', '2026-03-13 08:09:14'),
(82, 'Afrika', 'afrika', 81, 162, 169, 1, 0, '2026-03-13 07:58:55', '2026-03-13 07:59:36'),
(83, 'Marokko', 'marokko', 82, 163, 164, 2, 0, '2026-03-13 07:59:11', '2026-03-13 07:59:11'),
(84, 'Südafrika', 'sudafrika', 82, 165, 166, 2, 1, '2026-03-13 07:59:22', '2026-03-13 07:59:22'),
(85, 'Ägypten', 'agypten', 82, 167, 168, 2, 2, '2026-03-13 07:59:36', '2026-03-13 07:59:36'),
(86, 'Amerika', 'amerika', 81, 170, 177, 1, 1, '2026-03-13 07:59:50', '2026-03-13 08:00:57'),
(87, 'Lateinamerika', 'lateinamerika', 86, 171, 172, 2, 0, '2026-03-13 08:00:02', '2026-03-13 08:00:02'),
(88, 'Mexico', 'mexico', 86, 173, 174, 2, 1, '2026-03-13 08:00:21', '2026-03-13 08:00:21'),
(89, 'USA & Kanada', 'usa-kanada', 86, 175, 176, 2, 2, '2026-03-13 08:00:57', '2026-03-13 08:00:57'),
(90, 'Asien', 'asien', 81, 178, 191, 1, 2, '2026-03-13 08:01:21', '2026-03-13 08:03:01'),
(91, 'Chinesisch', 'chinesisch', 90, 179, 180, 2, 0, '2026-03-13 08:01:29', '2026-03-13 08:01:29'),
(92, 'Indisch', 'indisch', 90, 181, 182, 2, 1, '2026-03-13 08:02:27', '2026-03-13 08:02:27'),
(93, 'Japanisch', 'japanisch', 90, 183, 184, 2, 2, '2026-03-13 08:02:35', '2026-03-13 08:02:35'),
(94, 'Koreanisch', 'koreanisch', 90, 185, 186, 2, 3, '2026-03-13 08:02:43', '2026-03-13 08:02:43'),
(95, 'Thailand', 'thailand', 90, 187, 188, 2, 4, '2026-03-13 08:02:52', '2026-03-13 08:02:52'),
(96, 'Vietnam', 'vietnam', 90, 189, 190, 2, 5, '2026-03-13 08:03:01', '2026-03-13 08:03:01'),
(97, 'Australien', 'australien', 81, 192, 193, 1, 3, '2026-03-13 08:03:11', '2026-03-13 08:03:11'),
(98, 'Europa', 'europa', 81, 194, 241, 1, 4, '2026-03-13 08:03:19', '2026-03-13 08:08:33'),
(99, 'Belgien', 'belgien', 98, 195, 196, 2, 0, '2026-03-13 08:03:30', '2026-03-13 08:03:30'),
(100, 'Deutschland', 'deutschland', 98, 197, 198, 2, 1, '2026-03-13 08:03:39', '2026-03-13 08:03:39'),
(101, 'Dänemark', 'danemark', 98, 199, 200, 2, 2, '2026-03-13 08:03:50', '2026-03-13 08:03:50'),
(102, 'Finnland', 'finnland', 98, 201, 202, 2, 3, '2026-03-13 08:04:00', '2026-03-13 08:04:00'),
(103, 'Frankreich', 'frankreich', 98, 203, 204, 2, 4, '2026-03-13 08:04:10', '2026-03-13 08:04:10'),
(104, 'Griechenland', 'griechenland', 98, 205, 206, 2, 5, '2026-03-13 08:04:23', '2026-03-13 08:04:23'),
(105, 'Großbritannien & Irland', 'grossbritannien-irland', 98, 207, 208, 2, 6, '2026-03-13 08:04:33', '2026-03-13 08:04:33'),
(106, 'Italien', 'italien', 98, 209, 210, 2, 7, '2026-03-13 08:04:42', '2026-03-13 08:04:42'),
(107, 'Luxemburg', 'luxemburg', 98, 211, 212, 2, 8, '2026-03-13 08:04:49', '2026-03-13 08:04:49'),
(108, 'Malta', 'malta', 98, 213, 214, 2, 9, '2026-03-13 08:05:03', '2026-03-13 08:05:03'),
(109, 'Niederlande', 'niederlande', 98, 215, 216, 2, 10, '2026-03-13 08:05:13', '2026-03-13 08:05:13'),
(110, 'Norwegen', 'norwegen', 98, 217, 218, 2, 11, '2026-03-13 08:05:24', '2026-03-13 08:05:24'),
(111, 'Polen', 'polen', 98, 219, 220, 2, 12, '2026-03-13 08:05:36', '2026-03-13 08:05:36'),
(112, 'Portugal', 'portugal', 98, 221, 222, 2, 13, '2026-03-13 08:05:46', '2026-03-13 08:05:46'),
(113, 'Russland', 'russland', 98, 223, 224, 2, 14, '2026-03-13 08:07:00', '2026-03-13 08:07:00'),
(114, 'Schweden', 'schweden', 98, 225, 226, 2, 15, '2026-03-13 08:07:13', '2026-03-13 08:07:13'),
(115, 'Schweiz', 'schweiz', 98, 227, 228, 2, 16, '2026-03-13 08:07:22', '2026-03-13 08:07:22'),
(116, 'Spanien', 'spanien', 98, 229, 230, 2, 17, '2026-03-13 08:07:30', '2026-03-13 08:07:30'),
(117, 'Tschechien', 'tschechien', 98, 231, 232, 2, 18, '2026-03-13 08:07:39', '2026-03-13 08:07:39'),
(118, 'Türkei', 'turkei', 98, 233, 234, 2, 19, '2026-03-13 08:07:54', '2026-03-13 08:07:54'),
(119, 'Ungarn', 'ungarn', 98, 235, 236, 2, 20, '2026-03-13 08:08:04', '2026-03-13 08:08:04'),
(120, 'Weißrussland', 'weissrussland', 98, 237, 238, 2, 21, '2026-03-13 08:08:18', '2026-03-13 08:08:18'),
(121, 'Österreich', 'osterreich', 98, 239, 240, 2, 22, '2026-03-13 08:08:33', '2026-03-13 08:08:33'),
(122, 'Karibik & Exotik', 'karibik-exotik', 81, 242, 243, 1, 5, '2026-03-13 08:08:47', '2026-03-13 08:08:47'),
(123, 'Mittlerer und Naher Osten', 'mittlerer-und-naher-osten', 81, 244, 245, 1, 6, '2026-03-13 08:09:02', '2026-03-13 08:09:02'),
(124, 'Osteuropa', 'osteuropa', 81, 246, 247, 1, 7, '2026-03-13 08:09:14', '2026-03-13 08:09:14'),
(125, 'Getränke', 'getraenke', NULL, 249, 266, 0, 5, '2026-03-13 08:09:26', '2026-03-13 08:29:47'),
(126, 'Bowle', 'bowle', 125, 250, 251, 1, 0, '2026-03-13 08:09:38', '2026-03-13 08:09:38'),
(127, 'Cocktail', 'cocktail', 125, 252, 257, 1, 1, '2026-03-13 08:09:55', '2026-03-13 08:10:25'),
(128, 'Alkoholfrei', 'alkoholfrei', 127, 253, 254, 2, 0, '2026-03-13 08:10:05', '2026-03-13 08:10:05'),
(129, 'Longdrink', 'longdrink', 127, 255, 256, 2, 1, '2026-03-13 08:10:25', '2026-03-13 08:10:25'),
(130, 'Kaffee, Tee & Kakao', 'kaffee-tee-kakao', 125, 258, 259, 1, 2, '2026-03-13 08:10:37', '2026-03-13 08:10:37'),
(131, 'Likör', 'likor', 125, 260, 261, 1, 3, '2026-03-13 08:10:50', '2026-03-13 08:10:50'),
(132, 'Punsch', 'punsch', 125, 262, 263, 1, 4, '2026-03-13 08:11:02', '2026-03-13 08:11:02'),
(133, 'Shake', 'shake', 125, 264, 265, 1, 5, '2026-03-13 08:11:10', '2026-03-13 08:11:10'),
(134, 'Menüart', 'menueart', NULL, 267, 348, 0, 6, '2026-03-13 08:11:26', '2026-03-13 08:30:17'),
(135, 'Beilage', 'beilage-menuart', 134, 268, 281, 1, 0, '2026-03-13 08:12:23', '2026-03-13 08:15:21'),
(136, 'Hülsenfrüchte', 'hulsenfruchte', 135, 269, 270, 2, 0, '2026-03-13 08:13:43', '2026-03-13 08:13:43'),
(137, 'Kartoffeln', 'kartoffeln', 135, 271, 272, 2, 1, '2026-03-13 08:14:02', '2026-03-13 08:14:02'),
(138, 'Klöße', 'klosse', 135, 273, 274, 2, 2, '2026-03-13 08:14:16', '2026-03-13 08:14:16'),
(139, 'Nudeln', 'nudeln', 135, 275, 276, 2, 3, '2026-03-13 08:14:25', '2026-03-13 08:14:25'),
(140, 'Reis/Getreide', 'reisgetreide', 135, 277, 278, 2, 4, '2026-03-13 08:14:37', '2026-03-13 08:14:37'),
(141, 'Gemüse', 'gemuse-beilage', 135, 279, 280, 2, 5, '2026-03-13 08:15:11', '2026-03-13 08:15:11'),
(142, 'Dessert', 'dessert', 134, 282, 283, 1, 1, '2026-03-13 08:15:56', '2026-03-13 08:15:56'),
(143, 'Frühstück', 'fruhstuck-menuart', 134, 284, 285, 1, 2, '2026-03-13 08:16:23', '2026-03-13 08:16:23'),
(144, 'Hauptspeise', 'hauptspeise', 134, 286, 313, 1, 3, '2026-03-13 08:17:03', '2026-03-13 08:22:01'),
(145, 'Eintopf', 'eintopf-hauptspeise', 144, 287, 288, 2, 0, '2026-03-13 08:17:32', '2026-03-13 08:17:32'),
(146, 'Fisch', 'fisch-hauptspeise', 144, 289, 290, 2, 1, '2026-03-13 08:18:25', '2026-03-13 08:18:25'),
(147, 'Eier', 'eier', 144, 291, 292, 2, 2, '2026-03-13 08:18:53', '2026-03-13 08:18:53'),
(148, 'Geflügel', 'geflugel', 144, 293, 294, 2, 3, '2026-03-13 08:19:03', '2026-03-13 08:19:03'),
(149, 'Krustentier & Muscheln', 'krustentier-muscheln-hauptspeise', 144, 295, 296, 2, 4, '2026-03-13 08:19:42', '2026-03-13 08:19:42'),
(150, 'Innereien', 'innereien', 144, 297, 298, 2, 5, '2026-03-13 08:20:09', '2026-03-13 08:20:22'),
(151, 'Lamm & Ziege', 'lamm-ziege', 144, 299, 300, 2, 6, '2026-03-13 08:20:45', '2026-03-13 08:20:45'),
(152, 'Pasta & Nudel', 'pasta-nudel', 144, 301, 302, 2, 7, '2026-03-13 08:20:56', '2026-03-13 08:20:56'),
(153, 'Pilze', 'pilze', 144, 303, 304, 2, 8, '2026-03-13 08:21:11', '2026-03-13 08:21:11'),
(154, 'Pizza', 'pizza', 144, 305, 306, 2, 9, '2026-03-13 08:21:27', '2026-03-13 08:21:27'),
(155, 'Rind', 'rind', 144, 307, 308, 2, 10, '2026-03-13 08:21:40', '2026-03-13 08:21:40'),
(156, 'Schwein', 'schwein', 144, 309, 310, 2, 11, '2026-03-13 08:21:50', '2026-03-13 08:21:50'),
(157, 'Wild & Kaninchen', 'wild-kaninchen', 144, 311, 312, 2, 12, '2026-03-13 08:22:01', '2026-03-13 08:22:01'),
(158, 'Salat', 'salat', 134, 314, 331, 1, 4, '2026-03-13 08:22:21', '2026-03-13 08:25:55'),
(159, 'Reis/Nudeln/Getreide', 'reisnudelngetreide', 158, 315, 316, 2, 0, '2026-03-13 08:22:37', '2026-03-13 08:22:37'),
(160, 'Eier & Käse', 'eier-kase', 158, 317, 318, 2, 1, '2026-03-13 08:22:47', '2026-03-13 08:22:47'),
(161, 'Fleisch & Wurst', 'fleisch-wurst', 158, 319, 320, 2, 2, '2026-03-13 08:23:06', '2026-03-13 08:24:49'),
(162, 'Früchte', 'fruchte', 158, 321, 322, 2, 3, '2026-03-13 08:23:13', '2026-03-13 08:24:08'),
(163, 'Kartoffel', 'kartoffel', 158, 323, 324, 2, 4, '2026-03-13 08:23:27', '2026-03-13 08:23:27'),
(164, 'Krustentier & Fisch', 'krustentier-fisch', 158, 325, 326, 2, 5, '2026-03-13 08:25:30', '2026-03-13 08:25:30'),
(165, 'Salatdressing', 'salatdressing', 158, 327, 328, 2, 6, '2026-03-13 08:25:45', '2026-03-13 08:25:45'),
(166, 'Warme Salate', 'warme-salate', 158, 329, 330, 2, 7, '2026-03-13 08:25:55', '2026-03-13 08:25:55'),
(167, 'Suppen', 'suppen', 134, 332, 341, 1, 5, '2026-03-13 08:26:09', '2026-03-13 08:26:46'),
(168, 'Einlagen', 'einlagen', 167, 333, 334, 2, 0, '2026-03-13 08:26:17', '2026-03-13 08:26:17'),
(169, 'Gebundene', 'gebundene', 167, 335, 336, 2, 1, '2026-03-13 08:26:25', '2026-03-13 08:26:25'),
(170, 'Klare', 'klare', 167, 337, 338, 2, 2, '2026-03-13 08:26:36', '2026-03-13 08:26:36'),
(171, 'Spezial', 'spezial', 167, 339, 340, 2, 3, '2026-03-13 08:26:46', '2026-03-13 08:26:46'),
(172, 'Vorspeisen', 'vorspeisen', 134, 342, 347, 1, 6, '2026-03-13 08:26:59', '2026-03-13 08:27:15'),
(173, 'Kalt', 'kalt', 172, 343, 344, 2, 0, '2026-03-13 08:27:06', '2026-03-13 08:27:06'),
(174, 'Warm', 'warm', 172, 345, 346, 2, 1, '2026-03-13 08:27:15', '2026-03-13 08:27:15');