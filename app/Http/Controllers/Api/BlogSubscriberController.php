<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\BlogSubscriberResource\FarewellSubscriberMail;
use App\Mail\BlogSubscriberResource\VerifySubscriberMail;
use App\Mail\BlogSubscriberResource\WelcomeSubscriberMail;
use App\Models\BlogSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BlogSubscriberController extends Controller
{
  /**
   * @OA\Get(
   *     path="/api/blog-subscribers/subscribe/{email}",
   *     summary="Subscribe to newsletter",
   *     description="Subscribe an email to the blog newsletter. A unique token will be generated for verification and unsubscribe purposes.",
   *     tags={"Blog Subscribers"},
   *     @OA\Parameter(name="email", in="path", required=true, description="Email address to subscribe", @OA\Schema(type="string", format="email")),
   *     @OA\Response(response=201, description="Subscribed successfully", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function subscribe(string $email): JsonResponse
  {
    $email = textLower($email);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => ['email' => ['The email must be a valid email address.']]
      ], 422);
    }

    $existingSubscriber = BlogSubscriber::where('email', $email)->first();

    if ($existingSubscriber) {
      return response()->json([
        'success' => false,
        'message' => 'Email already subscribed',
        'errors' => ['email' => ['The email has already been subscribed.']]
      ], 422);
    }

    $subscriber = BlogSubscriber::create([
      'email' => $email,
      'name' => explode('@', $email)[0],
      'token' => Str::uuid7()->toString(),
      'subscribed_at' => now(),
    ]);

    Mail::to($subscriber->email)->send(new VerifySubscriberMail([
      'name' => $subscriber->name,
      'email' => $subscriber->email,
      'token' => $subscriber->token,
    ]));

    return response()->json([
      'success' => true,
      'message' => 'Subscribed successfully',
      'data' => [
        'email' => $subscriber->email,
        'name' => $subscriber->name,
        'token' => $subscriber->token,
      ]
    ], 201);
  }

  /**
   * @OA\Get(
   *     path="/api/blog-subscribers/verify/{token}",
   *     summary="Verify subscription",
   *     description="Verify a subscription using the provided token.",
   *     tags={"Blog Subscribers"},
   *     @OA\Parameter(name="token", in="path", required=true, description="Unique verification token", @OA\Schema(type="string")),
   *     @OA\Response(response=200, description="Verified successfully", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=404, description="Invalid token", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
  public function verify(string $token): JsonResponse
  {
    $subscriber = BlogSubscriber::where('token', $token)->first();

    if (!$subscriber) {
      return response()->json([
        'success' => false,
        'message' => 'Invalid token'
      ], 404);
    }

    if ($subscriber->verified_at) {
      return response()->json([
        'success' => false,
        'message' => 'Email already verified'
      ], 400);
    }

    $subscriber->update(['verified_at' => now()]);

    Mail::to($subscriber->email)->send(new WelcomeSubscriberMail([
      'name' => $subscriber->name,
      'email' => $subscriber->email,
      'token' => $subscriber->token,
    ]));

    return response()->json([
      'success' => true,
      'message' => 'Email verified successfully',
      'data' => [
        'email' => $subscriber->email,
        'verified_at' => $subscriber->verified_at->toISOString(),
      ]
    ]);
  }

  /**
   * @OA\Get(
   *     path="/api/blog-subscribers/unsubscribe/{token}",
   *     summary="Unsubscribe from newsletter",
   *     description="Unsubscribe from the blog newsletter using the provided token.",
   *     tags={"Blog Subscribers"},
   *     @OA\Parameter(name="token", in="path", required=true, description="Unique subscription token", @OA\Schema(type="string")),
   *     @OA\Response(response=200, description="Unsubscribed successfully", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=404, description="Invalid token", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
  public function unsubscribe(string $token): JsonResponse
  {
    $subscriber = BlogSubscriber::where('token', $token)->first();

    if (!$subscriber) {
      return response()->json([
        'success' => false,
        'message' => 'Invalid token'
      ], 404);
    }

    if ($subscriber->unsubscribed_at) {
      return response()->json([
        'success' => false,
        'message' => 'Already unsubscribed'
      ], 400);
    }

    $newToken = Str::uuid7()->toString();

    $subscriber->update([
      'unsubscribed_at' => now(),
      'verified_at' => null,
      'token' => $newToken,
    ]);

    $subscriber->refresh();

    Mail::to($subscriber->email)->send(new FarewellSubscriberMail([
      'name' => $subscriber->name,
      'email' => $subscriber->email,
      'token' => $newToken,
    ]));

    return response()->json([
      'success' => true,
      'message' => 'Unsubscribed successfully',
      'data' => [
        'email' => $subscriber->email,
        'unsubscribed_at' => $subscriber->unsubscribed_at->toISOString(),
      ]
    ]);
  }

  /**
   * @OA\Get(
   *     path="/api/blog-subscribers/{token}",
   *     summary="Get subscriber details",
   *     description="Retrieve subscriber information by token.",
   *     tags={"Blog Subscribers"},
   *     @OA\Parameter(name="token", in="path", required=true, description="Unique subscription token", @OA\Schema(type="string")),
   *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=404, description="Not found", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
  public function show(string $token): JsonResponse
  {
    $subscriber = BlogSubscriber::where('token', $token)->first();

    if (!$subscriber) {
      return response()->json([
        'success' => false,
        'message' => 'Subscriber not found'
      ], 404);
    }

    return response()->json([
      'success' => true,
      'message' => 'Subscriber retrieved successfully',
      'data' => [
        'email' => $subscriber->email,
        'name' => $subscriber->name,
        'verified_at' => $subscriber->verified_at?->toISOString(),
        'subscribed_at' => $subscriber->subscribed_at?->toISOString(),
        'unsubscribed_at' => $subscriber->unsubscribed_at?->toISOString(),
      ]
    ]);
  }

  /**
   * @OA\Get(
   *     path="/api/blog-subscribers/re-subscribe/{token}",
   *     summary="Re-subscribe to newsletter",
   *     description="Re-subscribe to the blog newsletter using the token from farewell email.",
   *     tags={"Blog Subscribers"},
   *     @OA\Parameter(name="token", in="path", required=true, description="Unique re-subscription token", @OA\Schema(type="string")),
   *     @OA\Response(response=200, description="Re-subscribed successfully", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=404, description="Invalid token", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
   * )
   */
  public function reSubscribe(string $token): JsonResponse
  {
    $subscriber = BlogSubscriber::where('token', $token)->first();

    if (!$subscriber) {
      return response()->json([
        'success' => false,
        'message' => 'Invalid token'
      ], 404);
    }

    if (!$subscriber->unsubscribed_at) {
      return response()->json([
        'success' => false,
        'message' => 'Already subscribed'
      ], 400);
    }

    $subscriber->update([
      'subscribed_at' => now(),
      'unsubscribed_at' => null,
      'verified_at' => now(),
    ]);

    Mail::to($subscriber->email)->send(new WelcomeSubscriberMail([
      'name' => $subscriber->name,
      'email' => $subscriber->email,
      'token' => $subscriber->token,
    ]));

    return response()->json([
      'success' => true,
      'message' => 'Re-subscribed successfully',
      'data' => [
        'email' => $subscriber->email,
        'subscribed_at' => $subscriber->subscribed_at->toISOString(),
        'verified_at' => $subscriber->verified_at->toISOString(),
      ]
    ]);
  }
}
