<?php

namespace App\Services\AI;

use App\Events\AiConversationEscalated;
use App\Models\AiAction;
use App\Models\AiConversationState;
use App\Models\AiEscalation;
use App\Models\AiFollowUp;
use App\Models\AiKnowledge;
use App\Models\AiSetting;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\Product;
use App\Services\ConversationMessageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AiBusinessTools
{
    public function __construct(
        private readonly ConversationMessageService $messages,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function definitions(): array
    {
        return array_map(
            static fn (array $tool): array => [
                'type' => 'function',
                'function' => $tool,
            ],
            [
                [
                    'name' => 'search_products',
                    'description' => 'Search active catalogue products using real names, SKU, size, category, and stock.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => 'Product name, category, SKU, or mattress type.'],
                            'size' => ['type' => 'string', 'description' => 'Requested product size if provided.'],
                        ],
                        'required' => ['query'],
                        'additionalProperties' => false,
                    ],
                ],
                [
                    'name' => 'get_product',
                    'description' => 'Retrieve current public details for one active product by catalogue ID.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => ['product_id' => ['type' => 'integer']],
                        'required' => ['product_id'],
                        'additionalProperties' => false,
                    ],
                ],
                [
                    'name' => 'send_product_image',
                    'description' => 'Send the exact catalogue image for an active product. Never use for a different product.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_id' => ['type' => 'integer'],
                            'caption' => ['type' => 'string'],
                        ],
                        'required' => ['product_id'],
                        'additionalProperties' => false,
                    ],
                ],
                [
                    'name' => 'get_business_information',
                    'description' => 'Retrieve admin-approved business, FAQ, delivery, payment, promotion, return, or warranty information. Empty results mean the information is not configured.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'topic' => ['type' => 'string'],
                            'query' => ['type' => 'string'],
                        ],
                        'required' => ['topic', 'query'],
                        'additionalProperties' => false,
                    ],
                ],
                [
                    'name' => 'record_conversation_state',
                    'description' => 'Persist the customer intent, conversation stage, concise summary, and next action after processing this turn.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'intent' => ['type' => 'string'],
                            'stage' => [
                                'type' => 'string',
                                'enum' => [
                                    'GREETING', 'DISCOVERY', 'PRODUCT_SEARCH',
                                    'PRODUCT_RECOMMENDATION', 'PRODUCT_COMPARISON',
                                    'IMAGE_REQUEST', 'PRICE_DISCUSSION', 'CUSTOMER_DETAILS',
                                    'DELIVERY_DETAILS', 'ORDER_REVIEW', 'PAYMENT',
                                    'DELIVERY', 'POST_SALE', 'FOLLOW_UP',
                                    'CLOSED',
                                ],
                            ],
                            'summary' => ['type' => 'string'],
                            'next_action' => ['type' => 'string'],
                            'language' => ['type' => 'string', 'enum' => ['sw', 'en']],
                            'confidence' => ['type' => 'number', 'minimum' => 0, 'maximum' => 1],
                            'discussed_product_ids' => [
                                'type' => 'array',
                                'items' => ['type' => 'integer'],
                            ],
                        ],
                        'required' => ['intent', 'stage', 'summary', 'next_action', 'language', 'confidence'],
                        'additionalProperties' => false,
                    ],
                ],
                [
                    'name' => 'update_customer_details',
                    'description' => 'Save customer details explicitly provided in this WhatsApp conversation. The verified WhatsApp phone cannot be changed.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string'],
                            'location' => ['type' => 'string'],
                            'area' => ['type' => 'string'],
                            'district' => ['type' => 'string'],
                        ],
                        'additionalProperties' => false,
                    ],
                ],
                [
                    'name' => 'check_order_status',
                    'description' => 'Check only this WhatsApp customer’s real order and delivery status.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'order_number' => ['type' => 'string'],
                        ],
                        'additionalProperties' => false,
                    ],
                ],
                [
                    'name' => 'request_order_confirmation',
                    'description' => 'Prepare an exact order summary and request explicit customer confirmation. This tool never creates an order.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'product_id' => ['type' => 'integer'],
                            'quantity' => ['type' => 'integer'],
                            'customer_name' => ['type' => 'string'],
                            'location' => ['type' => 'string'],
                            'area' => ['type' => 'string'],
                            'delivery_address' => ['type' => 'string'],
                        ],
                        'required' => ['product_id', 'quantity', 'customer_name', 'location', 'area', 'delivery_address'],
                        'additionalProperties' => false,
                    ],
                ],
                [
                    'name' => 'schedule_follow_up',
                    'description' => 'Schedule one permitted follow-up only when follow-ups are enabled and the customer has not opted out.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'reason' => ['type' => 'string'],
                            'product_id' => ['type' => 'integer'],
                        ],
                        'required' => ['reason'],
                        'additionalProperties' => false,
                    ],
                ],
                [
                    'name' => 'escalate_to_human',
                    'description' => 'Immediately stop autonomous replies and notify the WGP team by marking this conversation for human takeover.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'reason' => ['type' => 'string'],
                            'intent' => ['type' => 'string'],
                            'summary' => ['type' => 'string'],
                            'recommended_action' => ['type' => 'string'],
                        ],
                        'required' => ['reason', 'summary'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        );
    }

    /**
     * Execute only explicitly registered, validated tools.
     *
     * @param array<string, mixed> $arguments
     */
    public function execute(
        string $name,
        array $arguments,
        Conversation $conversation,
        AiConversationState $state,
        int $inboundMessageId,
    ): string {
        try {
            $result = match ($name) {
                'search_products' => $this->searchProducts($arguments, $state),
                'get_product' => $this->getProduct($arguments, $state),
                'send_product_image' => $this->sendProductImage($arguments, $conversation, $state),
                'get_business_information' => $this->getBusinessInformation($arguments),
                'record_conversation_state' => $this->recordConversationState(
                    $arguments,
                    $state,
                    $inboundMessageId,
                ),
                'update_customer_details' => $this->updateCustomerDetails($arguments, $conversation),
                'check_order_status' => $this->checkOrderStatus($arguments, $conversation->customer),
                'request_order_confirmation' => $this->requestOrderConfirmation(
                    $arguments,
                    $conversation,
                    $state,
                    $inboundMessageId,
                ),
                'schedule_follow_up' => $this->scheduleFollowUp(
                    $arguments,
                    $conversation,
                    $state,
                    $inboundMessageId,
                ),
                'escalate_to_human' => $this->escalate($arguments, $conversation, $state),
                default => throw new RuntimeException('Requested AI tool is not permitted.'),
            };

            AiAction::query()->create([
                'conversation_id' => $conversation->id,
                'customer_id' => $conversation->customer_id,
                'order_id' => $result['order_id'] ?? null,
                'actor' => 'ai',
                'tool' => $name,
                'status' => 'completed',
                'summary' => $result['summary'],
                'metadata' => $result['metadata'] ?? null,
            ]);

            return json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $exception) {
            AiAction::query()->create([
                'conversation_id' => $conversation->id,
                'customer_id' => $conversation->customer_id,
                'actor' => 'ai',
                'tool' => $name,
                'status' => 'failed',
                'summary' => 'AI tool execution failed.',
                'metadata' => ['error_type' => class_basename($exception)],
            ]);

            throw $exception;
        }

    }

    /** @param array<string, mixed> $arguments */
    private function recordConversationState(
        array $arguments,
        AiConversationState $state,
        int $inboundMessageId,
    ): array
    {
        $input = Validator::make($arguments, [
            'intent' => ['required', 'string', 'max:80'],
            'stage' => [
                'required',
                'in:GREETING,DISCOVERY,PRODUCT_SEARCH,PRODUCT_RECOMMENDATION,PRODUCT_COMPARISON,IMAGE_REQUEST,PRICE_DISCUSSION,CUSTOMER_DETAILS,DELIVERY_DETAILS,ORDER_REVIEW,PAYMENT,DELIVERY,POST_SALE,FOLLOW_UP,CLOSED',
            ],
            'summary' => ['required', 'string', 'max:1500'],
            'next_action' => ['required', 'string', 'max:500'],
            'language' => ['required', 'in:sw,en'],
            'confidence' => ['required', 'numeric', 'between:0,1'],
            'discussed_product_ids' => ['sometimes', 'array', 'max:20'],
            'discussed_product_ids.*' => ['integer', 'min:1'],
        ])->validate();

        $productIds = array_values(array_unique(array_map('intval', $input['discussed_product_ids'] ?? [])));
        $activeIds = Product::query()->active()->whereIn('id', $productIds)->pluck('id')->all();
        $context = $state->context ?? [];
        $context['discussed_product_ids'] = $activeIds;
        $context['confidence_message_id'] = $inboundMessageId;
        $stage = $state->stage === 'AWAITING_CONFIRMATION'
            ? 'AWAITING_CONFIRMATION'
            : $input['stage'];
        $state->update([
            'intent' => trim($input['intent']),
            'stage' => $stage,
            'summary' => trim($input['summary']),
            'next_action' => trim($input['next_action']),
            'language' => $input['language'],
            'confidence' => (float) $input['confidence'],
            'context' => $context,
        ]);

        return ['summary' => 'Persisted conversation state.'];
    }

    /** @param array<string, mixed> $arguments */
    private function searchProducts(array $arguments, AiConversationState $state): array
    {
        $input = Validator::make($arguments, [
            'query' => ['required', 'string', 'max:180'],
            'size' => ['nullable', 'string', 'max:100'],
        ])->validate();

        $query = Product::query()
            ->active()
            ->with('category')
            ->where(function ($builder) use ($input): void {
                $term = trim($input['query']);
                $builder
                    ->where('name', 'like', '%' . $term . '%')
                    ->orWhere('sku', 'like', '%' . $term . '%')
                    ->orWhereHas('category', fn ($category) => $category->where('name', 'like', '%' . $term . '%'));
            })
            ->when(
                filled($input['size'] ?? null),
                fn ($builder) => $builder->where('size', 'like', '%' . trim($input['size']) . '%'),
            )
            ->orderBy('name')
            ->limit(8)
            ->get();

        $this->trackCatalogueProducts($state, $query->pluck('id')->all());

        return [
            'summary' => 'Searched the active product catalogue.',
            'products' => $query->map(fn (Product $product): array => $this->publicProductData($product))->all(),
        ];
    }

    /** @param array<string, mixed> $arguments */
    private function getProduct(array $arguments, AiConversationState $state): array
    {
        $input = Validator::make($arguments, [
            'product_id' => ['required', 'integer', 'min:1'],
        ])->validate();

        $product = Product::query()->active()->with('category')->find($input['product_id']);
        if ($product) {
            $this->trackCatalogueProducts($state, [$product->id]);
        }

        return [
            'summary' => $product ? 'Returned the current catalogue record.' : 'No active catalogue product matched that ID.',
            'product' => $product ? $this->publicProductData($product) : null,
        ];
    }

    /** @param array<string, mixed> $arguments */
    private function sendProductImage(
        array $arguments,
        Conversation $conversation,
        AiConversationState $state,
    ): array
    {
        $input = Validator::make($arguments, [
            'product_id' => ['required', 'integer', 'min:1'],
            'caption' => ['nullable', 'string', 'max:500'],
        ])->validate();
        $product = Product::query()->active()->find($input['product_id']);

        if (! $product) {
            return ['summary' => 'No image is available for the selected active product.'];
        }
        $verifiedProductIds = array_map(
            'intval',
            data_get($state->context, 'verified_catalogue_product_ids', []),
        );
        if (! in_array((int) $product->id, $verifiedProductIds, true)) {
            return [
                'summary' => 'The product image cannot be sent until this product has been retrieved from the active catalogue.',
                'catalogue_lookup_required' => true,
            ];
        }
        if (blank($product->image_path)) {
            return ['summary' => 'No image is available for the selected active product.'];
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($product->image_path)) {
            throw new RuntimeException('The selected catalogue image is unavailable.');
        }

        $imageUrl = url($disk->url($product->image_path));
        if (! filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('The selected catalogue image URL is invalid.');
        }

        $caption = trim((string) ($input['caption'] ?? ''));
        $message = $this->messages->sendImage(
            $conversation,
            $imageUrl,
            $caption !== '' ? $caption : $product->name . ' · ' . ($product->size ?: 'WGP Catalogue'),
            null,
            $disk->mimeType($product->image_path) ?: null,
            basename($product->image_path),
        );
        $state->update(['last_ai_message_at' => now()]);

        return [
            'summary' => 'Sent the exact active catalogue image.',
            'metadata' => ['product_id' => $product->id, 'outbound_message_id' => $message->id],
        ];
    }

    /** @param array<string, mixed> $arguments */
    private function getBusinessInformation(array $arguments): array
    {
        $input = Validator::make($arguments, [
            'topic' => ['required', 'in:business,faq,sales,delivery,payment,warranty,returns,promotion,escalation,rules'],
            'query' => ['required', 'string', 'max:180'],
        ])->validate();
        $term = trim($input['query']);

        $knowledge = AiKnowledge::query()
            ->where('is_active', true)
            ->where(function ($builder) use ($input, $term): void {
                $builder->where('category', $input['topic']);
                if ($term !== '') {
                    $builder->where(function ($search) use ($term): void {
                        $search
                            ->where('title', 'like', '%' . $term . '%')
                            ->orWhere('question', 'like', '%' . $term . '%')
                            ->orWhere('answer', 'like', '%' . $term . '%');
                    });
                }
            })
            ->orderBy('sort_order')
            ->limit(12)
            ->get(['category', 'title', 'question', 'answer', 'language']);

        return [
            'summary' => $knowledge->isEmpty()
                ? 'No administrator-approved information is configured for this query.'
                : 'Returned administrator-approved business information.',
            'knowledge' => $knowledge->all(),
        ];
    }

    /** @param array<string, mixed> $arguments */
    private function updateCustomerDetails(array $arguments, Conversation $conversation): array
    {
        $input = Validator::make($arguments, [
            'name' => ['sometimes', 'string', 'min:2', 'max:160'],
            'location' => ['sometimes', 'string', 'max:180'],
            'area' => ['sometimes', 'string', 'max:180'],
            'district' => ['sometimes', 'string', 'max:180'],
        ])->validate();

        if ($input === []) {
            throw ValidationException::withMessages(['details' => 'No customer details were provided.']);
        }

        $missingFields = array_values(array_filter(
            array_keys($input),
            fn (string $field): bool => ! $this->customerExplicitlyProvided($conversation, $input[$field]),
        ));
        if ($missingFields !== []) {
            return [
                'summary' => 'These details were not explicitly provided by the customer and were not saved.',
                'missing_customer_details' => $missingFields,
            ];
        }

        $conversation->customer->fill($input)->save();

        return [
            'summary' => 'Saved customer details provided in the conversation.',
            'metadata' => ['updated_fields' => array_keys($input)],
        ];
    }

    /** @param array<string, mixed> $arguments */
    private function checkOrderStatus(array $arguments, Customer $customer): array
    {
        $input = Validator::make($arguments, [
            'order_number' => ['nullable', 'string', 'max:80'],
        ])->validate();

        $order = $customer->orders()
            ->when(
                filled($input['order_number'] ?? null),
                fn ($query) => $query->where('order_number', trim($input['order_number'])),
            )
            ->latest('ordered_at')
            ->first();

        return [
            'summary' => $order ? 'Returned the current status of the customer’s own order.' : 'No matching order was found for this WhatsApp customer.',
            'order' => $order ? [
                'order_number' => $order->order_number,
                'product_name' => $order->product_name,
                'product_size' => $order->product_size,
                'quantity' => $order->quantity,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'delivery_status' => $order->delivery_status,
                'delivery_area' => $order->delivery_area,
                'ordered_at' => $order->ordered_at?->toIso8601String(),
            ] : null,
            'order_id' => $order?->id,
        ];
    }

    /** @param array<string, mixed> $arguments */
    private function requestOrderConfirmation(
        array $arguments,
        Conversation $conversation,
        AiConversationState $state,
        int $inboundMessageId,
    ): array {
        $input = Validator::make($arguments, [
            'product_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'customer_name' => ['required', 'string', 'min:2', 'max:160'],
            'location' => ['required', 'string', 'min:2', 'max:180'],
            'area' => ['required', 'string', 'min:2', 'max:180'],
            'delivery_address' => ['required', 'string', 'min:5', 'max:1000'],
        ])->validate();

        if (AiSetting::get('order_confirmation_required', 'true') !== 'true') {
            throw new RuntimeException('Explicit order confirmation is mandatory.');
        }
        if (AiSetting::get('auto_order_creation', 'true') !== 'true') {
            return [
                'summary' => 'AI order creation is disabled by an administrator; no order was prepared.',
                'order_creation_disabled' => true,
            ];
        }

        $product = Product::query()->active()->find($input['product_id']);
        if (! $product) {
            throw new RuntimeException('The selected product is not available in the active catalogue.');
        }
        $verifiedProductIds = array_map(
            'intval',
            data_get($state->context, 'verified_catalogue_product_ids', []),
        );
        if (! in_array((int) $product->id, $verifiedProductIds, true)) {
            return [
                'summary' => 'The product must first be retrieved from the active catalogue. No order was prepared.',
                'catalogue_lookup_required' => true,
            ];
        }
        if ((int) $product->stock_quantity < (int) $input['quantity']) {
            return [
                'summary' => 'The catalogue stock is insufficient; do not promise availability.',
                'unavailable' => true,
                'available_quantity' => (int) $product->stock_quantity,
            ];
        }

        $requiredCustomerValues = [
            'customer_name' => $input['customer_name'],
            'location' => $input['location'],
            'area' => $input['area'],
            'delivery_address' => $input['delivery_address'],
        ];
        $missingValues = [];
        foreach ($requiredCustomerValues as $field => $value) {
            if (! $this->customerExplicitlyProvided($conversation, $value)) {
                $missingValues[] = $field;
            }
        }
        if ($missingValues !== []) {
            return [
                'summary' => 'Some order details were not explicitly provided by the customer. Ask for those details; no customer data was changed and no order was prepared.',
                'missing_customer_details' => $missingValues,
            ];
        }

        $conversation->customer->update([
            'name' => trim($input['customer_name']),
            'location' => trim($input['location']),
            'area' => trim($input['area']),
        ]);

        $context = $state->context ?? [];
        $context['pending_order'] = [
            'product_id' => $product->id,
            'quantity' => (int) $input['quantity'],
            'delivery_address' => trim($input['delivery_address']),
            'delivery_area' => trim($input['area']),
            'prepared_from_message_id' => $inboundMessageId,
        ];
        $state->update([
            'context' => $context,
            'stage' => 'AWAITING_CONFIRMATION',
            'next_action' => 'Wait for explicit customer confirmation before creating the order.',
        ]);

        $total = (float) $product->price * (int) $input['quantity'];
        $summary = $state->language === 'en'
            ? implode("\n", [
                'Your order summary:',
                'Product: ' . $product->name,
                'Size: ' . ($product->size ?: 'Not specified'),
                'Quantity: ' . (int) $input['quantity'],
                'Price each: TSh ' . number_format((float) $product->price, 0),
                'Product total: TSh ' . number_format($total, 0),
                'Area: ' . trim($input['area']),
                'Address: ' . trim($input['delivery_address']),
                '',
                'Please reply “Confirm” or “Nathibitisha” to approve this order. I will not place it until you confirm.',
            ])
            : implode("\n", [
            'Muhtasari wa oda yako:',
            'Bidhaa: ' . $product->name,
            'Size: ' . ($product->size ?: 'Haijaainishwa'),
            'Idadi: ' . (int) $input['quantity'],
            'Bei kwa moja: TSh ' . number_format((float) $product->price, 0),
            'Jumla ya bidhaa: TSh ' . number_format($total, 0),
            'Eneo: ' . trim($input['area']),
            'Anuani: ' . trim($input['delivery_address']),
            '',
            'Tafadhali jibu “Nathibitisha” au “Confirm” ili kuthibitisha oda. Sitaweka oda mpaka upate nafasi ya kuthibitisha.',
        ]);
        $outbound = $this->messages->sendText($conversation, $summary);
        $state->update(['last_ai_message_at' => now()]);

        return [
            'summary' => 'Sent a database-priced order summary and is awaiting an explicit customer confirmation; no order was created.',
            'awaiting_confirmation' => true,
            'product_id' => $product->id,
            'total_amount' => $total,
            'metadata' => ['outbound_message_id' => $outbound->id],
        ];
    }

    private function customerExplicitlyProvided(Conversation $conversation, string $value): bool
    {
        $normalize = static fn (string $text): string => Str::lower(
            Str::squish((string) preg_replace('/[^\pL\pN\s]/u', ' ', $text)),
        );
        $normalizedValue = $normalize($value);
        if ($normalizedValue === '') {
            return false;
        }

        $customerText = $conversation->messages()
            ->where('direction', 'inbound')
            ->whereIn('message_type', ['text', 'interactive', 'button'])
            ->latest('id')
            ->limit(60)
            ->pluck('body')
            ->implode(' ');

        return Str::contains(
            ' ' . $normalize($customerText) . ' ',
            ' ' . $normalizedValue . ' ',
        );
    }

    /** @param array<int, int|string> $productIds */
    private function trackCatalogueProducts(AiConversationState $state, array $productIds): void
    {
        $context = $state->fresh()->context ?? [];
        $existing = array_map('intval', data_get($context, 'verified_catalogue_product_ids', []));
        $context['verified_catalogue_product_ids'] = array_slice(
            array_values(array_unique([...$existing, ...array_map('intval', $productIds)])),
            -30,
        );
        $state->update(['context' => $context]);
    }

    /**
     * Called only after the orchestrator has independently verified a short
     * explicit confirmation from the current customer message.
     */
    public function createConfirmedOrder(
        Conversation $conversation,
        AiConversationState $state,
        int $inboundMessageId,
    ): ?Order {
        return DB::transaction(function () use ($conversation, $state, $inboundMessageId): ?Order {
            $lockedState = AiConversationState::query()
                ->whereKey($state->id)
                ->lockForUpdate()
                ->firstOrFail();
            $context = $lockedState->context ?? [];
            $pending = $context['pending_order'] ?? null;
            $idempotencyKey = 'wgp-ai-order:' . $conversation->id . ':' . $inboundMessageId;
            $existingAction = AiAction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if (
                $lockedState->status !== 'active'
                ||
                $lockedState->stage !== 'AWAITING_CONFIRMATION'
                || ! is_array($pending)
                || empty($pending['product_id'])
            ) {
                if ($existingAction?->order_id) {
                    return Order::find($existingAction->order_id);
                }

                return null;
            }

            if ($existingAction?->order_id) {
                return Order::find($existingAction->order_id);
            }

            $product = Product::query()
                ->active()
                ->lockForUpdate()
                ->find($pending['product_id']);

            if (
                ! $product
                || (int) $product->stock_quantity < (int) ($pending['quantity'] ?? 0)
            ) {
                throw new RuntimeException('The product or required stock is no longer available.');
            }

            $quantity = (int) $pending['quantity'];
            $unitPrice = (float) $product->price;
            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $conversation->customer_id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_size' => $product->size,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $unitPrice * $quantity,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'delivery_status' => 'pending',
                'delivery_address' => (string) $pending['delivery_address'],
                'delivery_area' => (string) $pending['delivery_area'],
                'notes' => 'Created by WGP AI after explicit customer confirmation.',
                'ordered_at' => now(),
            ]);

            OrderActivity::query()->create([
                'order_id' => $order->id,
                'user_id' => null,
                'type' => 'ai_order_initiated',
                'description' => 'AI prepared an order summary; the customer explicitly confirmed before order creation.',
                'metadata' => [
                    'source' => 'ai_agent',
                    'prepared_from_message_id' => $pending['prepared_from_message_id'] ?? null,
                ],
            ]);

            OrderActivity::query()->create([
                'order_id' => $order->id,
                'user_id' => null,
                'type' => 'customer_confirmed',
                'description' => 'Customer explicitly confirmed the order through WhatsApp.',
                'metadata' => [
                    'source' => 'whatsapp_customer_confirmation',
                    'customer_confirmation_message_id' => $inboundMessageId,
                ],
            ]);

            OrderActivity::query()->create([
                'order_id' => $order->id,
                'user_id' => null,
                'type' => 'created',
                'description' => 'Order created by AI after explicit customer confirmation.',
                'metadata' => [
                    'source' => 'ai_agent',
                    'customer_confirmation_message_id' => $inboundMessageId,
                ],
            ]);

            AiAction::query()->create([
                'conversation_id' => $conversation->id,
                'customer_id' => $conversation->customer_id,
                'order_id' => $order->id,
                'actor' => 'ai',
                'tool' => 'create_confirmed_order',
                'status' => 'completed',
                'summary' => 'Created an order after explicit WhatsApp confirmation.',
                'idempotency_key' => $idempotencyKey,
                'metadata' => [
                    'customer_confirmation_message_id' => $inboundMessageId,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                ],
            ]);

            unset($context['pending_order']);
            $lockedState->update([
                'context' => $context,
                'stage' => 'ORDER_CONFIRMED',
                'next_action' => 'Provide the created order number and current order status.',
            ]);

            $conversation->aiFollowUps()
                ->where('status', 'scheduled')
                ->update(['status' => 'cancelled']);

            return $order;
        });
    }

    /** @param array<string, mixed> $arguments */
    private function scheduleFollowUp(
        array $arguments,
        Conversation $conversation,
        AiConversationState $state,
        int $inboundMessageId,
    ): array {
        $input = Validator::make($arguments, [
            'reason' => ['required', 'string', 'min:3', 'max:300'],
            'product_id' => ['nullable', 'integer', 'min:1'],
        ])->validate();

        if (AiSetting::get('auto_follow_up', 'false') !== 'true') {
            return ['summary' => 'Follow-ups are disabled by the administrator.'];
        }
        if (($state->context['opted_out'] ?? false) === true) {
            return ['summary' => 'The customer has opted out of follow-ups.'];
        }
        if (($state->context['follow_up_opt_in'] ?? false) !== true) {
            return ['summary' => 'An explicit customer opt-in is required before scheduling a follow-up.'];
        }

        $max = max(0, (int) AiSetting::get('maximum_follow_ups', '2'));
        $used = AiFollowUp::query()
            ->where('customer_id', $conversation->customer_id)
            ->whereIn('status', ['scheduled', 'sent'])
            ->where('created_at', '>=', now()->subDays(30))
            ->count();
        if ($max === 0 || $used >= $max) {
            return ['summary' => 'The configured maximum number of follow-ups has been reached.'];
        }

        $productId = $input['product_id'] ?? data_get($state->context, 'pending_order.product_id');
        if ($productId && ! Product::query()->active()->whereKey($productId)->exists()) {
            throw new RuntimeException('The requested follow-up product is not active.');
        }

        $delay = max(5, (int) AiSetting::get('follow_up_delay_minutes', '60'));
        $followUp = AiFollowUp::query()->firstOrCreate(
            ['idempotency_key' => 'wgp-ai-follow-up:' . $conversation->id . ':' . $inboundMessageId],
            [
                'conversation_id' => $conversation->id,
                'customer_id' => $conversation->customer_id,
                'message_id' => $inboundMessageId,
                'status' => 'scheduled',
                'attempt' => $used + 1,
                'scheduled_at' => now()->addMinutes($delay),
                'metadata' => [
                    'reason' => trim($input['reason']),
                    'product_id' => $productId,
                ],
            ],
        );

        return [
            'summary' => $followUp->wasRecentlyCreated
                ? 'Scheduled an approved follow-up.'
                : 'This inbound message already has a scheduled follow-up.',
            'scheduled_at' => $followUp->scheduled_at?->toIso8601String(),
        ];
    }

    /** @param array<string, mixed> $arguments */
    private function escalate(
        array $arguments,
        Conversation $conversation,
        AiConversationState $state,
    ): array {
        $input = Validator::make($arguments, [
            'reason' => ['required', 'string', 'min:3', 'max:180'],
            'intent' => ['nullable', 'string', 'max:80'],
            'summary' => ['required', 'string', 'min:5', 'max:3000'],
            'recommended_action' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $escalation = AiEscalation::query()->firstOrCreate(
            ['conversation_id' => $conversation->id, 'status' => 'open'],
            [
                'customer_id' => $conversation->customer_id,
                'status' => 'open',
                'reason' => trim($input['reason']),
                'intent' => $input['intent'] ?? $state->intent,
                'summary' => trim($input['summary']),
                'products_discussed' => data_get($state->context, 'discussed_product_ids', []),
                'recommended_action' => $input['recommended_action'] ?? null,
            ],
        );

        $state->update([
            'status' => 'escalated',
            'stage' => 'ESCALATED',
            'intent' => $input['intent'] ?? $state->intent,
            'summary' => trim($input['summary']),
            'next_action' => $input['recommended_action'] ?? 'A WGP administrator should take over this conversation.',
        ]);
        AiConversationEscalated::dispatch($conversation->id);

        return [
            'summary' => 'Escalated this conversation and disabled autonomous replies.',
            'escalation_id' => $escalation->id,
        ];
    }

    /** @return array<string, mixed> */
    private function publicProductData(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'category' => $product->category?->name,
            'size' => $product->size,
            'price' => (float) $product->price,
            'currency' => 'TSh',
            'stock_quantity' => (int) $product->stock_quantity,
            'availability' => (int) $product->stock_quantity > 0 ? 'in_stock' : 'out_of_stock',
            'description' => $product->description,
            'image_available' => filled($product->image_path)
                && Storage::disk('public')->exists($product->image_path),
        ];
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'WGP-' . now()->format('YmdHis') . '-' . random_int(100, 999);
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
