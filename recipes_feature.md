Neues Plugin implementieren.
Im Ordner plugins/ddk, author ist Ddk

Ziel: Es soll möglich sein, Rezepte mit dem Plugin auf dem Backend anzulegen und im Frontend anzuzeigen. Rezepte können nach ihrem Titel durchsucht werden (Volltextsuche). Die Suche soll Rechtschreibfehlertolerant sein. Wenn es also in der Datenbank die Tabelle "Spaghetti" gibt, möchte ich bis zu einem Gewissen grad auch ergebnisse finden können, wenn ich nach "pageti" suche, sortiert nach dem besten match.

"ddk_recipe_ingredients" Tabelle: Hier werden Zutaten gelistet, z.B. Salz, Pfeffer, Tomate, Farfalle, ...
Zutaten haben eine eindeutige id und ein title

"ddk_recipe_units" Tabelle: Hier werden Einheiten gelistet, z.B. TL, mg, Bund, Glas, Dose, ...
Einheiten haben eine eindeutige id und ein title

"ddk_recipe_categories" Tabelle: Hier werden Kategorien gelistet, z.B. Backen & Süßspeisen, Brot und Brötchen, Regional, Europa, Deutschland, ... Die Kategorien können nested sein, also Regionoal > Europa > Deutschland.
Kategorien haben eine eindeutige id, title, parent_category_id (optional), sort_order

"ddk_recipe_ingredients_map" Tabelle: Verbindet ddk_recipe_ingredients mit ddk_recipes
id, ddk_recipe_id, recipe_ingredient_id, ddk_recipe_unit_id, quantity (kann kommazahl sein), sort_order, note (optional)

"ddk_recipe_categories_map" Tabelle: verbindet die Kategorien mit ddk_recipes
id, ddk_recipe_id, ddk_recipe_category_id

"ddk_recipe_steps_map" Tabelle: alle einzelnen Schritte pro ddk_recipes
id, step_number, title (optional), description

"ddk_recipes" Tabelle: Kerntabelle für Rezepte. Hier wird der title vergeben und ein subtitle. der text hat auch eine description für einen langen text. portions geben die Portionen an. difficulty kann einfach, mittel, schwer sein. dann gibt es noch working_time_minutes und cooking_time_minutes, author_id (siehe user plugin), rezepte haben logischerweise auch eine id


ich möchte in der lage sein im backend ddk_recipe_ingredients, ddk_recipe_units, ddk_recipe_categories und ddk_recipes pflegen können, in dafür vorgesehenen eigene bereiche erstellen, bearbeiten und löschen.

diese schritte werden dann zukünftig angegangen:
- ich möchte, dass auf der homepage Empfohlene Rezepte und Rezepte nach Kategorie verlinkt werden
- ich möchte die rezepte dann auf der http://localhost:8000/rezepte page finden können
- ich möchte das rezept dann auf der http://localhost:8000/rezept/thai-curry page im detail sehen können
- ich möchte, dass die suche wie gewollt funktioniert
