<?php

use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores the editor html alongside its plain text', function () {
    $note = Note::factory()->create();

    expect($note->body_html)->toStartWith('<')
        ->and($note->body_text)->not->toBeEmpty();
});

it('titles a note from the first plain-text line', function () {
    $note = Note::factory()->create([
        'body_html' => '<h1>Groceries</h1><p>Milk and eggs</p>',
        'body_text' => "Groceries\nMilk and eggs",
    ]);

    expect($note->title())->toBe('Groceries')
        ->and($note->excerpt())->toBe('Milk and eggs');
});

it('has a showcase note covering the whole HTML contract', function () {
    $note = Note::factory()->showcase()->create();

    expect($note->body_html)
        ->toContain('<h1>')
        ->toContain('<ul><li>')
        ->toContain('<blockquote>')
        ->toContain('<a href=')
        ->toContain('<code>');
});
