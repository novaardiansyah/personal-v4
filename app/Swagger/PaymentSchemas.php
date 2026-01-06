<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
  schema: "PaymentStoreRequest",
  required: ["date", "type_id", "payment_account_id"],
  properties: [
    new OA\Property(property: "amount", type: "number", example: 50000, description: "Payment amount (required if has_items is false)"),
    new OA\Property(property: "date", type: "string", format: "date", example: "2024-12-15", description: "Payment date"),
    new OA\Property(property: "name", type: "string", example: "Lunch expense", description: "Payment description"),
    new OA\Property(property: "type_id", type: "integer", example: 1, description: "1=Expense, 2=Income, 3=Transfer, 4=Withdrawal"),
    new OA\Property(property: "payment_account_id", type: "integer", example: 1, description: "Source payment account ID"),
    new OA\Property(property: "payment_account_to_id", type: "integer", example: 2, description: "Destination (for Transfer/Withdrawal)"),
    new OA\Property(property: "has_items", type: "boolean", example: false),
    new OA\Property(property: "is_scheduled", type: "boolean", example: false),
    new OA\Property(property: "is_draft", type: "boolean", example: false),
    new OA\Property(property: "request_view", type: "boolean", example: false)
  ]
)]
class PaymentSchemas
{
}
