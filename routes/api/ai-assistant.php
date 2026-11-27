<?php

use App\Http\Controllers\Api\AiAssistantController;
use Illuminate\Support\Facades\Route;

Route::prefix('ai-assistant')->group(function () {
  Route::get('/contexts', [AiAssistantController::class, 'contexts']);
  Route::get('/sessions', [AiAssistantController::class, 'sessions']);
  Route::post('/sessions', [AiAssistantController::class, 'createSession']);
  Route::get('/sessions/{uuid}/messages', [AiAssistantController::class, 'messages']);
  Route::post('/sessions/{uuid}/messages', [AiAssistantController::class, 'sendMessage']);
  Route::post('/sessions/{uuid}/actions/{actionId}/confirm', [AiAssistantController::class, 'confirmAction']);
  Route::delete('/sessions/{uuid}', [AiAssistantController::class, 'deleteSession']);
});
