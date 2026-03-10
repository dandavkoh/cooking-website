# DDK API Plugin

REST API für Winter CMS zur programmatischen Verwaltung von Inhalten.

## Installation

1. Plugin-Dateien in `plugins/ddk/api` platzieren
2. Migrations ausführen:
   ```bash
   php artisan winter:up
   ```

## Authentifizierung

Die API verwendet Bearer Token Authentifizierung.

### Token generieren

```bash
php artisan api:generate-token <backend-username>
```

**Wichtig:** Der Token wird nur einmal angezeigt. Speichern Sie ihn sicher!

### Token verwenden

```bash
curl -X POST https://your-site.com/api/v1/recipes \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '...'
```

### Token widerrufen

```bash
php artisan api:generate-token <backend-username> --revoke
```

## Berechtigungen

Benutzer benötigen die Berechtigung `ddk.api.access_api` um die API nutzen zu können.

## Endpoints

### Rezepte

#### POST /api/v1/recipes

Erstellt ein vollständiges Rezept mit allen Relationen in einer transaktionalen Anfrage.

**Request Body:**

```json
{
  "title": "Spaghetti Carbonara",
  "subtitle": "Klassisch italienisch",
  "description": "Ein cremiges Pasta-Gericht mit Ei und Speck...",
  "slug": "spaghetti-carbonara",
  "portions": 4,
  "difficulty": 2,
  "working_time_minutes": 20,
  "cooking_time_minutes": 15,
  "author_id": 1,
  "published": true,
  "categories": [1, 2],
  "ingredients": [
    {
      "name": "Spaghetti",
      "quantity": 500,
      "unit": "g",
      "note": "am besten Nr. 5"
    },
    {
      "name": "Guanciale",
      "quantity": 200,
      "unit": "g"
    },
    {
      "name": "Eigelb",
      "quantity": 4,
      "unit": "Stück"
    },
    {
      "name": "Pecorino Romano",
      "quantity": 100,
      "unit": "g"
    }
  ],
  "steps": [
    {
      "title": "Pasta kochen",
      "description": "Großen Topf mit Salzwasser aufsetzen und Spaghetti nach Packungsanleitung al dente kochen."
    },
    {
      "title": "Guanciale anbraten",
      "description": "Guanciale in Streifen schneiden und bei mittlerer Hitze knusprig braten."
    },
    {
      "title": "Sauce zubereiten",
      "description": "Eigelb mit geriebenem Pecorino vermengen und mit Pfeffer würzen."
    }
  ],
  "images": [
    {
      "data": "base64-encoded-image-data",
      "filename": "carbonara-fertig.jpg",
      "content_type": "image/jpeg"
    }
  ]
}
```

**Beispiel**
```
php artisan api:generate-token admin

IMG=$(base64 -i /path/to/image.png | tr -d '\n')

cat <<EOF | curl -X POST "http://localhost:8000/api/v1/recipes" \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d @-
{
  "title": "Spaghetti Carbonara",
  "subtitle": "Klassisch italienisch",
  "description": "Ein cremiges Pasta-Gericht...",
  "slug": "spaghetti-carbonara",
  "portions": 4,
  "difficulty": 2,
  "working_time_minutes": 20,
  "cooking_time_minutes": 15,
  "author_id": 1,
  "published": true,
  "categories": [1, 2],
  "ingredients": [
    {"name": "Spaghetti", "quantity": 500, "unit": "g", "note": "Nr. 5"},
    {"name": "Guanciale", "quantity": 200, "unit": "g"}
  ],
  "steps": [
    {"title": "Pasta kochen", "description": "Salzwasser aufsetzen..."},
    {"description": "Guanciale anbraten..."}
  ],
  "images": [
    {
      "data": "$IMG",
      "filename": "image_20260310_195613.png",
      "content_type": "image/png"
    }
  ]
}
EOF
```

**Feldübersicht:**

| Feld | Typ | Erforderlich | Beschreibung |
|------|-----|--------------|--------------|
| `title` | string | ✓ | Rezepttitel (max. 255 Zeichen) |
| `subtitle` | string | | Untertitel |
| `description` | string | | Ausführliche Beschreibung |
| `slug` | string | | URL-Slug (wird automatisch aus Titel generiert wenn nicht angegeben) |
| `portions` | integer | | Anzahl der Portionen |
| `difficulty` | integer | ✓ | 1=einfach, 2=mittel, 3=schwer |
| `working_time_minutes` | integer | | Arbeitszeit in Minuten |
| `cooking_time_minutes` | integer | | Kochzeit in Minuten |
| `author_id` | integer | ✓ | ID des Autors (users Tabelle) |
| `published` | boolean | | Sofort veröffentlichen (Standard: false) |
| `categories` | array | ✓ | Array von Kategorie-IDs |
| `ingredients` | array | ✓ | Array von Zutaten-Objekten |
| `steps` | array | ✓ | Array von Zubereitungsschritt-Objekten |
| `images` | array | | Array von Base64-kodierten Bildern |

**Zutaten-Objekt:**

| Feld | Typ | Erforderlich | Beschreibung |
|------|-----|--------------|--------------|
| `name` | string | ✓ | Name der Zutat (wird angelegt falls nicht vorhanden) |
| `quantity` | number | ✓ | Menge |
| `unit` | string | ✓ | Einheit (wird angelegt falls nicht vorhanden) |
| `note` | string | | Optionale Anmerkung |

**Schritt-Objekt:**

| Feld | Typ | Erforderlich | Beschreibung |
|------|-----|--------------|--------------|
| `title` | string | | Titel des Schritts |
| `description` | string | ✓ | Beschreibung des Schritts |

*Hinweis: Die Schrittnummer wird automatisch aus der Reihenfolge im Array abgeleitet.*

**Bild-Objekt:**

| Feld | Typ | Erforderlich | Beschreibung |
|------|-----|--------------|--------------|
| `data` | string | ✓ | Base64-kodierte Bilddaten |
| `filename` | string | ✓ | Dateiname |
| `content_type` | string | | MIME-Type (wird automatisch erkannt) |

Unterstützte Bildformate: JPEG, PNG, WebP

**Erfolgreiche Antwort (201 Created):**

```json
{
  "success": true,
  "message": "Rezept erfolgreich erstellt.",
  "data": {
    "id": 42,
    "title": "Spaghetti Carbonara",
    "slug": "spaghetti-carbonara",
    "difficulty": 2,
    "total_time_minutes": 35,
    "is_published": true,
    "published_at": "2026-03-10T14:30:00+00:00",
    "categories": [...],
    "ingredients": [...],
    "steps": [...],
    "images": [...]
  }
}
```

#### GET /api/v1/recipes

Listet alle Rezepte mit Paginierung.

**Query Parameter:**

| Parameter | Typ | Standard | Beschreibung |
|-----------|-----|----------|--------------|
| `page` | integer | 1 | Seitennummer |
| `per_page` | integer | 15 | Einträge pro Seite (max. 100) |
| `published` | boolean | | Nach Veröffentlichungsstatus filtern |
| `category_id` | integer | | Nach Kategorie filtern |
| `search` | string | | Suche im Titel |

#### GET /api/v1/recipes/{id}

Gibt ein einzelnes Rezept mit allen Relationen zurück.

#### DELETE /api/v1/recipes/{id}

Löscht ein Rezept und alle zugehörigen Daten.

## Fehler-Responses

**Validierungsfehler (422):**

```json
{
  "success": false,
  "message": "Validierung fehlgeschlagen.",
  "error_code": "VALIDATION_ERROR",
  "errors": {
    "title": ["Der Titel ist erforderlich."],
    "ingredients": ["Mindestens eine Zutat ist erforderlich."]
  }
}
```

**Nicht autorisiert (401):**

```json
{
  "success": false,
  "message": "Invalid API token.",
  "error_code": "UNAUTHORIZED"
}
```

**Nicht gefunden (404):**

```json
{
  "success": false,
  "message": "Recipe with ID 999 not found.",
  "error_code": "NOT_FOUND"
}
```

## Erweiterbarkeit

Das Plugin ist für Erweiterungen konzipiert. Um neue API-Endpunkte hinzuzufügen (z.B. für Blog-Posts):

1. Neuen Request Validator in `http/requests/` erstellen
2. Neuen Service in `services/` erstellen
3. Neuen Controller in `controllers/api/` erstellen
4. Routes in `routes.php` hinzufügen

Beispiel für Blog-Erweiterung:

```php
// routes.php
Route::prefix('posts')->group(function () {
    Route::post('/', 'Ddk\Api\Controllers\Api\PostController@store');
    // ...
});
```

## Sicherheit

- Alle API-Anfragen erfordern Authentifizierung
- Tokens werden gehashed in der Datenbank gespeichert
- Benutzer benötigen explizite API-Berechtigungen
- Alle Operationen sind transaktional
