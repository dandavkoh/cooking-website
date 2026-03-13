## System Requirements (macOS)

* **Visual Studio Code**
* **MAMP 7.4.0**

  * PHP 8.3.30
  * Apache 2.4.66
  * MySQL 8.0.44
* **NVM 0.40.3**

  * node 24.13.0 / npm 11.6.2

---

## Suggested VS Code Extensions

* `wintercms.winter-cms`

---

## Initial Setup (macOS)

### 1. Download Winter CMS

Download the release archive:
[https://github.com/wintercms/winter/releases/tag/v1.2.10](https://github.com/wintercms/winter/releases/tag/v1.2.10)

---

### 2. Install dependencies and configure PATH

Install **MAMP**, **NVM** and **Composer**, then add **PHP** and **MySQL** from MAMP to your `PATH`.

Edit your shell profile:

```bash
nano ~/.zprofile
```

Add the following lines:

```bash
# MAMP PHP 8.3.30
export PATH="/Applications/MAMP/bin/php/php8.3.30/bin:$PATH"

# MAMP MySQL 8.0.44
export PATH="/Applications/MAMP/Library/bin/mysql80/bin:$PATH"
```

Reload the profile:

```bash
source ~/.zprofile
```

---

### 3. Enable required PHP extensions

Ensure all required PHP extensions are enabled in MAMP.
- cURL
- GD
- Mbstring
- OpenSSL
- PDO (including the drivers of your database server)
- SimpleXML
- ZipArchive

---

### 4. Review system requirements

Verify that your system meets the Winter CMS requirements:
[https://wintercms.com/docs/v1.2/docs/setup/installation#minimum-system-requirements](https://wintercms.com/docs/v1.2/docs/setup/installation#minimum-system-requirements)

---

### 5. Rename and open the downloaded release folder

```bash
mv winter-1.2.10 CookingWebsite
cd winter-1.2.10
```

---

### 6. Start MAMP services

Start **Apache** and **MySQL** in MAMP.

> Default MySQL credentials:
> **Username:** `root`
> **Password:** `root`

---

### 7. Create the database

```bash
mysql -u root -proot -P 8889 -h 127.0.0.1
```

```sql
CREATE DATABASE cooking;
EXIT;
```

---

### 8. Install Winter CMS

Navigate into the project directory and run:

```bash
composer install
php artisan winter:install
```

Serve the project locally:

```bash
php artisan serve
```

Maintenance and console scripts:
[https://wintercms.com/docs/v1.2/docs/console/setup-maintenance](https://wintercms.com/docs/v1.2/docs/console/setup-maintenance)

---

## Development Setup

> **Dev backend login (temporary):**
> Username: `admin`
> Password: `admin`

```bash
php artisan winter:env
php artisan serve
```

### Theme setup

```bash
php artisan create:theme cooking
php artisan winter:fresh
php artisan winter:up
php artisan theme:list
php artisan theme:use cooking
```

### Frontend tooling

```bash
cd themes/cooking
npm init -y
npm install -D tailwindcss @tailwindcss/cli @tailwindcss/forms @tailwindcss/typography autoprefixer postcss
npx @tailwindcss/cli init
```

> **Optional:** Install Vite for advanced asset compilation and Hot Module Replacement (HMR):
>
> ```bash
> npm install -D vite laravel-vite-plugin
> ```

---

### Theme structure adjustments


* Create the following folders:
  * `themes/cooking/assets/src/css`
  * `themes/cooking/assets/src/js`
* Create the files:
  * `themes/cooking/assets/src/css/theme-cooking.css`
  * `themes/cooking/assets/src/js/theme-cooking.js`

---

### Tailwind configuration

**1. Configure `tailwind.config.js`:**

```javascript
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './assets/src/js/**/*.{js,jsx}',
        './blocks/**/*.block',
        './layouts/**/*.htm',
        './pages/**/*.htm',
        './partials/**/*.htm',
        './content/**/*.htm',
        '../../../plugins/**/components/**/*.htm',
    ],
    plugins: [forms],
};
```

**2. Populate `assets/src/css/theme-cooking.css`:**

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

**3. Create `postcss.config.mjs`:**

```javascript
export default {
    plugins: {
        tailwindcss: {},
    },
};
```

**4. (Optional) Create `vite.config.mjs` for Vite:**

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

const defaultOutDir = 'assets/dist';
const baseUrl = process.env.VITE_BASE || '/themes/cooking';

export default defineConfig({
    envDir: '../../',
    base: `${process.env.VITE_BASE}/${defaultOutDir}`,
    build: {
        outDir: defaultOutDir,
        assetsDir: '',
    },
    plugins: [
        laravel({
            publicDirectory: defaultOutDir,
            input: [
                'assets/src/css/theme-cooking.css',
                'assets/src/js/theme-cooking.js',
            ],
            refresh: {
                paths: [
                    './**/*.htm',
                    './**/*.block',
                    'assets/src/**/*.css',
                    'assets/src/**/*.js',
                    'assets/src/**/*.jsx',
                ]
            },
        }),
    ],
});
```

**5. Add npm scripts to `package.json`:**

```json
"scripts": {
    "dev": "vite",
    "build": "vite build",
    "watch": "npx tailwindcss -i assets/src/css/theme-cooking.css -o assets/css/theme-cooking.css --watch"
}
```

---

### Development Workflow

**You need two terminal sessions running in parallel:**

**Terminal 1 - PHP Development Server:**

```bash
# From project root
php artisan serve
```

Runs on: `http://localhost:8000`

**Terminal 2 - Asset Compilation:**

```bash
# From project root
cd themes/cooking

# With Vite (recommended - includes HMR):
npm run dev

# Or with Tailwind CLI only:
npm run watch
```

Vite runs on: `http://localhost:5173` (automatically integrated via `vite()` helper in templates)

> **Tip:** Vite provides Hot Module Replacement (HMR), automatically refreshing your browser when you change CSS, JS, or template files.

---

### Update theme styles

Edit `themes/cooking/partials/meta/styles.htm`:

**With Vite:**

```wintercms
==
{{ vite(['assets/src/css/theme-cooking.css'], 'theme-cooking') }}

<style>
    :root {
        --primary: {{ this.theme.color_primary }};
        --secondary: {{ this.theme.color_secondary }};
    }
</style>

{% styles %}
{% placeholder head %}
```

**Without Vite (Tailwind CLI only):**

```wintercms
<link rel="stylesheet" href="{{ 'assets/css/theme-cooking.css'|theme }}">

{% styles %}
{% placeholder head %}
```

---

### Plugins

Add the **Blog**, **User** and **Forum** plugins to:

```
plugins/winter
```

> Naming convention: omit the `wn-` prefix and `-plugin` suffix.

Apply changes:

```bash
php artisan winter:up
```

---

## Database & Migrations

### Run all pending migrations

Execute all new migrations across all installed plugins:

```bash
php artisan winter:up
```

---

### Refresh a specific plugin

**Drops all tables and re-runs migrations** for a plugin (⚠️ destroys all data):

```bash
php artisan plugin:refresh Author.PluginName
```

Example:

```bash
php artisan plugin:refresh Ddk.Recipe
```

---

### Create a new migration

Generate a new migration file for a plugin:

```bash
php artisan create:migration Author.PluginName MigrationName
```

Example - creating a table:

```bash
php artisan create:migration Ddk.Recipes CreateRecipeTable
```

Example - updating an existing table:

```bash
php artisan create:migration Ddk.Recipes AddNewFieldsToRecipesTable
```

The migration file will be created in:

```
plugins/author/pluginname/updates/
```

---

### Migration best practices

**Initial table creation:**
Include all fields in the initial `create_table` migration when first developing the plugin.

**Adding fields to existing tables:**
If the table already exists and contains data, create a new migration:

```php
<?php namespace Author\Plugin\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class AddAddressFieldsToTable extends Migration
{
    public function up()
    {
        Schema::table('author_plugin_table', function($table)
        {
            $table->string('new_field')->nullable();
        });
    }

    public function down()
    {
        Schema::table('author_plugin_table', function($table)
        {
            $table->dropColumn('new_field');
        });
    }
}
```

**Register migrations:**
Each migration must be listed in `version.yaml`:

```yaml
1.0.1:
    - First version
    - create_recipes_table.php
1.0.2:
    - Added new fields
    - add_new_fields_to_recipes_table.php
```

---

## Winter CMS Resources

* **GitHub:** [https://github.com/wintercms/winter](https://github.com/wintercms/winter)
* **Homepage:** [https://wintercms.com/](https://wintercms.com/)
* **Installation Guide:**
  [https://wintercms.com/docs/v1.2/docs/setup/installation#minimum-system-requirements](https://wintercms.com/docs/v1.2/docs/setup/installation#minimum-system-requirements)
* **Documentation:** [https://wintercms.com/docs](https://wintercms.com/docs)
