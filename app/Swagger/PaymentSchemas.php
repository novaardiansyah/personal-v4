<?php

namespace App\Swagger;

/**
 * Payment Request/Response Schemas
 *
 * @OA\Schema(
 *     schema="PaymentStoreRequest",
 *     required={"date", "type_id", "payment_account_id"},
 *     @OA\Property(property="amount", type="number", example=50000, description="Payment amount (required if has_items is false)"),
 *     @OA\Property(property="date", type="string", format="date", example="2024-12-15", description="Payment date"),
 *     @OA\Property(property="name", type="string", example="Lunch expense", description="Payment description"),
 *     @OA\Property(property="type_id", type="integer", example=1, description="1=Expense, 2=Income, 3=Transfer, 4=Withdrawal"),
 *     @OA\Property(property="payment_account_id", type="integer", example=1, description="Source payment account ID"),
 *     @OA\Property(property="payment_account_to_id", type="integer", example=2, description="Destination (for Transfer/Withdrawal)"),
 *     @OA\Property(property="has_items", type="boolean", example=false),
 *     @OA\Property(property="has_charge", type="boolean", example=false),
 *     @OA\Property(property="is_scheduled", type="boolean", example=false),
 *     @OA\Property(property="is_draft", type="boolean", example=false),
 *     @OA\Property(property="request_view", type="boolean", example=false)
 * )
 */
class PaymentSchemas
{
}
