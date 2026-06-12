<?php

namespace App\Services;

use App\Http\Requests\Chatbot\ChatRequest;
use App\Http\Resources\ProductResource;
use App\Models\ChatbotSession;
use App\Models\ChatbotMessage;
use App\Models\ChatbotSetting;
use App\Models\Product;
use App\Models\DrugInteraction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ChatbotService
{
    public function chat(Request $request)
    {
        $messageText = $request->input('message');
        $sessionToken = $request->header('X-Session-Token') ?: $request->input('session_token');
        $user = auth('sanctum')->user();

        $session = null;
        if ($sessionToken) {
            $session = ChatbotSession::where('session_token', $sessionToken)
                            ->first();
        }

        if (!$session) {
            $sessionToken = Str::random(64);
            $session = ChatbotSession::create([
                'session_token' => $sessionToken,
                'user_id' => $user ? $user->id : null,
                'channel' => 'web',
                'started_at' => now(),
            ]);
        }

        ChatbotMessage::create([
            'session_id' => $session->id,
            'role' => 'user',
            'content' => strip_tags($messageText),
            'created_at' => now(),
        ]);

        $context = $this->retrieveContext($messageText);
        
        $history = ChatbotMessage::where('session_id', $session->id)
            ->where('role', '!=', 'system')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->reverse()
            ->values();

        $promptSetting = ChatbotSetting::where('key', 'chatbot_system_prompt')->first();
        $defaultPrompt = "Bạn là Dược sĩ AI chuyên nghiệp của nhà thuốc PharmaVN. 
        Nhiệm vụ của bạn:
        1. Tư vấn sức khỏe tận tâm, chính xác dựa trên kiến thức y khoa.
        2. CHỈ gợi ý sản phẩm từ danh sách được cung cấp nếu sản phẩm đó THỰC SỰ liên quan và có ích cho tình trạng của khách hàng.
        3. Tuyệt đối KHÔNG nhắc đến hoặc gợi ý các sản phẩm không liên quan, ngay cả khi chúng có trong danh sách ngữ cảnh.
        4. Nếu không có sản phẩm nào phù hợp trong kho, hãy tư vấn hướng điều trị chung và khuyên khách đi khám bác sĩ nếu cần.
        5. Luôn giữ thái độ lịch sự, chuyên nghiệp và có trách nhiệm.";

        $systemPrompt = ($promptSetting && $promptSetting->value) ? $promptSetting->value : $defaultPrompt;
        
        $userContent = $this->buildUserContent($messageText, $context, $history);

        $apiKey = env('GEMINI_API_KEY');
        $model = env('GEMINI_MODEL', 'gemini-2.5-flash');

        $recommendedSkus = [];
        try {
            $response = Http::timeout(30)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'system_instruction' => [
                    'parts' => [
                        ['text' => $systemPrompt]
                    ]
                ],
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $userContent]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 2048,
                    'temperature' => 0.7,
                    'responseMimeType' => 'application/json',
                    'responseSchema' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'response' => [
                                'type' => 'STRING',
                                'description' => 'Nội dung phản hồi tư vấn của dược sĩ bằng Tiếng Việt (sử dụng định dạng Markdown cơ bản).'
                            ],
                            'recommended_skus' => [
                                'type' => 'ARRAY',
                                'items' => [
                                    'type' => 'STRING'
                                ],
                                'description' => 'Danh sách SKU của các sản phẩm thực sự phù hợp và khuyên dùng cho khách hàng trong số các sản phẩm được cung cấp ở danh mục THÔNG TIN THUỐC LIÊN QUAN. Không thêm SKU của sản phẩm không có trong danh sách được cung cấp. Nếu không có sản phẩm nào phù hợp, trả về mảng rỗng.'
                            ]
                        ],
                        'required' => ['response', 'recommended_skus']
                    ]
                ]
            ]);

            if ($response->failed()) {
                throw new \Exception('Gemini API Error: ' . $response->body());
            }

            $respData = $response->json();
            $rawContent = $respData['candidates'][0]['content']['parts'][0]['text'] ?? null;

            $assistantText = 'Xin lỗi, tôi không thể xử lý yêu cầu lúc này.';
            if ($rawContent) {
                $decoded = json_decode($rawContent, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $assistantText = $decoded['response'] ?? $rawContent;
                    $recommendedSkus = $decoded['recommended_skus'] ?? [];
                } else {
                    $assistantText = $rawContent;
                }
            }

        } catch (\Exception $e) {
            \Log::error('Chatbot Service Error (Gemini)', ['message' => $e->getMessage()]);
            $assistantText = 'Xin lỗi, tôi gặp chút trục trặc khi kết nối với hệ thống tư vấn. Bạn có thể thử lại sau giây lát nhé!';
            $recommendedSkus = [];
        }

        $intent = 'general';
        $lowerMsg = mb_strtolower($messageText);
        if (str_contains($lowerMsg, 'tương tác')) {
            $intent = 'drug_interaction';
        } elseif (str_contains($lowerMsg, 'liều')) {
            $intent = 'dosage_query';
        } elseif (str_contains($lowerMsg, 'triệu chứng')) {
            $intent = 'symptom_query';
        }

        $assistantMsg = ChatbotMessage::create([
            'session_id' => $session->id,
            'role' => 'assistant',
            'content' => $assistantText,
            'intent_detected' => $intent,
            'created_at' => now(),
        ]);

        $productRefs = [];
        if (!empty($context['products'])) {
            foreach ($context['products'] as $p) {
                // Only save and suggest products that are explicitly recommended by the AI via their SKU
                if (in_array(strtoupper($p->sku), array_map('strtoupper', $recommendedSkus))) {
                    DB::table('chatbot_product_refs')->insert([
                        'id' => (string) Str::uuid(),
                        'message_id' => $assistantMsg->id,
                        'product_id' => $p->id,
                        'ref_type' => 'suggestion'
                    ]);
                    $productRefs[] = $p;
                }
            }
        }

        return [
            'session_token' => $session->session_token,
            'message_id' => $assistantMsg->id,
            'response' => $assistantText,
            'products' => ProductResource::collection(collect($productRefs)),
        ];
    }

    public function getSessionHistory($sessionToken)
    {
        $session = ChatbotSession::where('session_token', $sessionToken)->first();
        if (!$session) return [];

        $messages = ChatbotMessage::where('session_id', $session->id)
            ->with(['productRefs.product.images' => function($q) {
                $q->where('is_primary', true);
            }])
            ->orderBy('created_at', 'asc')
            ->get();

        return $messages->map(function ($msg) {
            $formattedProducts = [];
            foreach ($msg->productRefs as $ref) {
                if ($ref->product) {
                    $p = $ref->product;
                    $primaryImg = $p->images->first();
                    $formattedProducts[] = [
                        'id' => $p->id,
                        'name' => $p->name,
                        'slug' => $p->slug,
                        'sku' => $p->sku,
                        'requires_prescription' => $p->requires_prescription,
                        'status' => $p->status,
                        'current_price' => $p->current_price,
                        'sale_price' => $p->sale_price,
                        'base_price' => $p->base_price,
                        'primary_image' => $primaryImg ? $primaryImg->url : null,
                    ];
                }
            }

            return [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'products' => $formattedProducts,
                'created_at' => $msg->created_at,
            ];
        });
    }

    public function getTrendingQuestions()
    {
        $manual = ChatbotSetting::where('key', 'manual_suggestions')->first();
        $manualSuggestions = ($manual && !empty($manual->value)) ? (array)$manual->value : [];
        $isAdminRequest = request()->is('*admin*');

        // Cache results for 1 day to ensure high performance
        $cacheKey = $isAdminRequest ? 'chatbot_trending_admin' : 'chatbot_trending_public';
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400, function () use ($isAdminRequest, $manualSuggestions) {
            // Get trending data from the last 30 days
            $trendingData = DB::table('chatbot_messages')
                ->where('role', 'user')
                ->where('created_at', '>=', now()->subDays(30))
                ->select(DB::raw("
                    LOWER(TRIM(TRAILING '?' FROM TRIM(TRAILING '!' FROM TRIM(TRAILING '.' FROM TRIM(content))))) as normalized_content
                "), DB::raw('COUNT(*) as count'))
                ->groupBy('normalized_content')
                ->orderByDesc('count')
                ->limit(20) // Get more to ensure we have enough after filtering
                ->get();

            // Map and normalize for consistency
            $trending = $trendingData->map(function($item) {
                return [
                    'content' => mb_convert_case($item->normalized_content, MB_CASE_TITLE, "UTF-8"),
                    'count' => $item->count
                ];
            });

            // If public request, we need exactly 4 suggestions
            if (!$isAdminRequest) {
                $finalSuggestions = collect($manualSuggestions)->take(4);
                
                if ($finalSuggestions->count() < 4) {
                    $trendingTitles = $trending->pluck('content');
                    // Fill remaining slots with trending questions that are not already in manual suggestions
                    foreach ($trendingTitles as $title) {
                        if ($finalSuggestions->count() >= 4) break;
                        if (!$finalSuggestions->contains($title)) {
                            $finalSuggestions->push($title);
                        }
                    }
                }
                
                return $finalSuggestions->values()->all();
            }

            // Admin request gets full objects
            return $trending->values()->all();
        });
    }

    private function retrieveContext($messageText)
    {
        $stopwordSetting = ChatbotSetting::where('key', 'chatbot_stopwords')->first();
        $defaultStopwords = [
            'tôi', 'dùng', 'được', 'cho', 'của', 'là', 'có', 'không', 'với', 'cái',
            'này', 'nào', 'gì', 'đâu', 'đó', 'đi', 'lại', 'vào', 'ra', 'lên', 'xuống',
            'cần', 'muốn', 'phải', 'biết', 'thấy', 'làm', 'như', 'về', 'trong', 'ngoài',
            'thuốc', 'viên', 'hộp', 'chai', 'lọ', 'túi', 'miếng', 'ống', 'bơm', 'tiêm',
            'bị', 'và', 'nhưng', 'hoặc', 'cũng', 'đã', 'đang', 'sẽ', 'còn', 'nên', 'nhất',
            'quá', 'luôn', 'nha', 'nhé', 'ạ', 'ơi', 'em', 'anh', 'chị', 'bạn', 'mình'
        ];

        $stopwords = ($stopwordSetting && $stopwordSetting->value) ? $stopwordSetting->value : $defaultStopwords;

        // Clean punctuation from message to ensure accurate word splitting
        $cleanMessage = preg_replace('/[.,?!\/()]/u', ' ', $messageText);

        $queryWords = array_filter(explode(' ', mb_strtolower($cleanMessage)), function($w) use ($stopwords) {
            $trimmed = trim($w);
            return mb_strlen($trimmed) >= 2 && !in_array($trimmed, $stopwords);
        });

        // Re-index queryWords array
        $queryWords = array_values($queryWords);

        $products = collect();
        if (!empty($queryWords)) {
            $q = Product::with(['images' => function ($sub) {
                $sub->where('is_primary', true);
            }])->where('status', 'active');
            
            $q->where(function ($sub) use ($queryWords) {
                foreach ($queryWords as $word) {
                    $lowerWord = mb_strtolower($word);
                    if (mb_strlen($lowerWord) <= 3) {
                        // For short words, use simulated word boundaries with case-insensitive accent-sensitive LIKE BINARY
                        $sub->orWhere(DB::raw('LOWER(name)'), 'LIKE BINARY', "% {$lowerWord} %")
                            ->orWhere(DB::raw('LOWER(name)'), 'LIKE BINARY', "{$lowerWord} %")
                            ->orWhere(DB::raw('LOWER(name)'), 'LIKE BINARY', "% {$lowerWord}")
                            ->orWhere(DB::raw('LOWER(name)'), '=', $lowerWord)
                            ->orWhere(DB::raw('LOWER(active_ingredient)'), 'LIKE BINARY', "% {$lowerWord} %")
                            ->orWhere(DB::raw('LOWER(active_ingredient)'), 'LIKE BINARY', "{$lowerWord} %")
                            ->orWhere(DB::raw('LOWER(active_ingredient)'), 'LIKE BINARY', "% {$lowerWord}")
                            ->orWhere(DB::raw('LOWER(active_ingredient)'), '=', $lowerWord);
                    } else {
                        // For longer words, standard partial match using case-insensitive accent-sensitive LIKE BINARY is fine
                        $sub->orWhere(DB::raw('LOWER(name)'), 'LIKE BINARY', "%{$lowerWord}%")
                            ->orWhere(DB::raw('LOWER(active_ingredient)'), 'LIKE BINARY', "%{$lowerWord}%");
                    }
                }
            });
            // Calculate a relevance score based on the number of keyword matches to rank products correctly
            $selectScore = '0';
            foreach ($queryWords as $word) {
                $lowerWord = mb_strtolower($word);
                $escapedWord = str_replace("'", "''", $lowerWord);
                $selectScore .= " + (CASE WHEN LOWER(name) LIKE BINARY '%{$escapedWord}%' THEN 5 ELSE 0 END)";
                $selectScore .= " + (CASE WHEN LOWER(active_ingredient) LIKE BINARY '%{$escapedWord}%' THEN 5 ELSE 0 END)";
            }

            $products = $q->select('products.*')
                ->selectRaw("({$selectScore}) as relevance_score")
                ->orderByDesc('relevance_score')
                ->limit(4)
                ->get();
        }

        $interactions = collect();
        if ($products->isNotEmpty()) {
            $pIds = $products->pluck('id')->toArray();
            $interactions = DB::table('drug_interactions')
                ->whereIn('product_a_id', $pIds)
                ->orWhereIn('product_b_id', $pIds)
                ->get();
        }

        return [
            'products' => $products,
            'interactions' => $interactions,
        ];
    }

    private function buildUserContent($messageText, $context, $history)
    {
        $content = "";

        if ($context['products']->isNotEmpty()) {
            $content .= "THÔNG TIN THUỐC LIÊN QUAN:\n";
            foreach ($context['products'] as $p) {
                $content .= "- {$p->name} (SKU: {$p->sku}, Thành phần: {$p->active_ingredient}): {$p->usage}\n";
            }
            $content .= "\n";
        }

        if ($context['interactions']->isNotEmpty()) {
            $content .= "TƯƠNG TÁC THUỐC:\n";
            foreach ($context['interactions'] as $i) {
                $content .= "- Mức độ: {$i->severity}, Mô tả: {$i->description}\n";
            }
            $content .= "\n";
        }

        if ($history->isNotEmpty()) {
            $content .= "LỊCH SỬ HỘI THOẠI:\n";
            foreach ($history as $h) {
                $role = $h->role === 'assistant' ? 'Dược sĩ' : 'Khách hàng';
                $content .= "{$role}: {$h->content}\n";
            }
            $content .= "\n";
        }

        $content .= "CÂU HỎI: {$messageText}\n";
        $content .= "\nLƯU Ý QUAN TRỌNG: Hãy chọn những sản phẩm thực sự phù hợp để điều trị các triệu chứng được mô tả trong CÂU HỎI của khách hàng từ danh sách THÔNG TIN THUỐC LIÊN QUAN ở trên và điền mã SKU của chúng vào trường 'recommended_skus'. Tuyệt đối không giới thiệu sản phẩm không liên quan.";

        return $content;
    }
}
