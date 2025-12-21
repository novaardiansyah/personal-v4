<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\BlogSubscriberResource\VerifySubscriberMail;
use App\Mail\BlogSubscriberResource\WelcomeSubscriberMail;
use App\Models\BlogSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogSubscriberController extends Controller
{
  /**
   * @OA\Post(
   *     path="/api/blog-subscribers/subscribe",
   *     summary="Subscribe to newsletter",
   *     description="Subscribe an email to the blog newsletter. A unique token will be generated for verification and unsubscribe purposes.",
   *     tags={"Blog Subscribers"},
   *     @OA\RequestBody(required=true, @OA\JsonContent(
   *         required={"email"},
   *         @OA\Property(property="email", type="string", format="email", description="Email address to subscribe")
   *     )),
   *     @OA\Response(response=201, description="Subscribed successfully", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function subscribe(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'email' => 'required|email|unique:blog_subscribers,email',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    $subscriber = BlogSubscriber::create([
      'email' => textLower($validated['email']),
      'name' => explode('@', $validated['email'])[0],
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
   * @OA\Post(
   *     path="/api/blog-subscribers/verify",
   *     summary="Verify subscription",
   *     description="Verify a subscription using the provided token.",
   *     tags={"Blog Subscribers"},
   *     @OA\RequestBody(required=true, @OA\JsonContent(
   *         required={"token"},
   *         @OA\Property(property="token", type="string", description="Unique verification token")
   *     )),
   *     @OA\Response(response=200, description="Verified successfully", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=404, description="Invalid token", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function verify(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'token' => 'required|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    $subscriber = BlogSubscriber::where('token', $validated['token'])->first();

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
   * @OA\Post(
   *     path="/api/blog-subscribers/unsubscribe",
   *     summary="Unsubscribe from newsletter",
   *     description="Unsubscribe from the blog newsletter using the provided token.",
   *     tags={"Blog Subscribers"},
   *     @OA\RequestBody(required=true, @OA\JsonContent(
   *         required={"token"},
   *         @OA\Property(property="token", type="string", description="Unique subscription token")
   *     )),
   *     @OA\Response(response=200, description="Unsubscribed successfully", @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
   *     @OA\Response(response=404, description="Invalid token", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
   *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponse"))
   * )
   */
  public function unsubscribe(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'token' => 'required|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $validator->errors()
      ], 422);
    }

    $validated = $validator->validated();

    $subscriber = BlogSubscriber::where('token', $validated['token'])->first();

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

    $subscriber->update(['unsubscribed_at' => now()]);

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
}
