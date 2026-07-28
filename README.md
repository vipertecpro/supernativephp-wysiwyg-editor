# WysiwygEditor demo — NativePHP Mobile

A SuperNative demo app showcasing
[`vipertecpro/wysiwyg-editor`](https://github.com/vipertecpro/wysiwyg-editor):
a **fully native** WYSIWYG rich text editor for NativePHP Mobile that returns
clean, normalised HTML to PHP via an event.

The plugin lives in its **own repository** so anyone can drop it into their own
app; this repo exists purely to demonstrate it.

## The examples

Each screen opens the *same* plugin with a different configuration:

| Screen | What it shows |
| --- | --- |
| **Notes** | The full toolbar backed by SQLite — create, re-open and edit notes; the saved HTML round-trips losslessly |
| **Comment Box** | The `comment` preset — bold / italic / link only, 500-character cap with a live counter |
| **Composer** | Every tool enabled, with a toggle to inspect the **raw HTML** the plugin returned |
| **Branded Theme** | The editor recoloured with the host app's palette (`background`, `text`, `accent`, `highlight`) |

## Running it

```bash
composer install
php artisan migrate --seed
php artisan native:run ios
```

Use `android` instead of `ios` for the Android build. The first run downloads
the platform binaries and takes a while.

> This repo consumes the plugin through a Composer **path repository**
> (`../wysiwyg-editor`) so both can be developed side by side. To use the
> published package instead, drop that repository entry from `composer.json`
> and require `vipertecpro/wysiwyg-editor` normally.

## How the integration works

`App\Concerns\InteractsWithWysiwygEditor` holds the plumbing — it opens the
editor with the screen's own options and handles the result events, so each
example screen is just a preview plus a config:

```php
use Vipertecpro\WysiwygEditor\Events\ContentSaved;
use Vipertecpro\WysiwygEditor\Facades\WysiwygEditor;

WysiwygEditor::open($html, ['preset' => 'comment', 'maxLength' => 500]);

#[On(ContentSaved::class)]
public function onSaved(string $html, string $text): void { /* … */ }
```

Native views have no HTML display element, so `App\Support\RichText` turns the
plugin's HTML into block arrays that `resources/views/native/partials/rich-preview.blade.php`
renders with `<text>` elements.

## Tests

```bash
php artisan test
```

Covers the `RichText` helper, the `Note` model, and the editor screens
themselves through NativePHP's component testing harness — asserting the right
bridge calls go out and that saved content lands in the database.
