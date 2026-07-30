<?php

use App\NativeComponents\CommentBox;
use App\NativeComponents\Composer;
use App\NativeComponents\Home;
use App\NativeComponents\LinkedInFeed;
use App\NativeComponents\Notes;
use App\NativeComponents\ThemedEditor;
use App\NativeComponents\XTimeline;
use Illuminate\Support\Facades\Route;

Route::native('/', Home::class);
Route::native('/notes', Notes::class);
Route::native('/comments', CommentBox::class);
Route::native('/composer', Composer::class);
Route::native('/themed', ThemedEditor::class);
Route::native('/x', XTimeline::class);
Route::native('/linkedin', LinkedInFeed::class);
