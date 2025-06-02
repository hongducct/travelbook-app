<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private $apiKey;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key') ?? env('GEMINI_API_KEY');
    }

    /**
     * Generate response for travel-related queries
     */
    public function generateChatResponse($message, $tourContext = [])
    {
        try {
            $prompt = $this->buildTravelPrompt($message, $tourContext);
            return $this->callGeminiAPI($prompt);
        } catch (\Exception $e) {
            Log::error('Travel chat response error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate response for general queries (NEW)
     */
    public function generateGeneralResponse($message, $context = [])
    {
        try {
            $prompt = $this->buildGeneralPrompt($message, $context);
            return $this->callGeminiAPI($prompt);
        } catch (\Exception $e) {
            Log::error('General response error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build prompt for travel queries
     */
    private function buildTravelPrompt($message, $tourContext)
    {
        $contextText = '';

        if (!empty($tourContext['tours'])) {
            $contextText = "Dữ liệu tour có sẵn:\n";
            foreach (array_slice($tourContext['tours'], 0, 5) as $tour) {
                $contextText .= "- {$tour['name']} ({$tour['location']}) - {$tour['duration']} - {$tour['price_formatted']}\n";
            }
        }

        $availableLocations = implode(', ', $tourContext['locations'] ?? []);

        $prompt = "Bạn là trợ lý du lịch AI thông minh, chuyên tư vấn tour du lịch Việt Nam.

{$contextText}

Địa điểm có sẵn: {$availableLocations}

Câu hỏi của khách: {$message}

Hãy trả lời một cách tự nhiên, thân thiện như một chuyên gia du lịch. Nếu có tour phù hợp trong dữ liệu, hãy giới thiệu cụ thể. Nếu không có, hãy tư vấn chung về địa điểm đó.

Trả lời bằng tiếng Việt, khoảng 2-3 câu, không quá dài dòng.";

        return $prompt;
    }

    /**
     * Build prompt for general queries (NEW)
     */
    private function buildGeneralPrompt($message, $context = [])
    {
        $topics = implode(', ', $context['topics'] ?? []);

        $prompt = "Bạn là một AI assistant thông minh, có thể trả lời câu hỏi về nhiều lĩnh vực.

Câu hỏi: {$message}";

        // Add specific context based on topic
        if (!empty($context['topics'])) {
            $prompt .= "\nChủ đề liên quan: {$topics}";

            if (in_array('mathematics', $context['topics'])) {
                $prompt .= "\nHãy giải thích rõ ràng các bước tính toán nếu có.";
            }

            if (in_array('science', $context['topics'])) {
                $prompt .= "\nHãy giải thích khoa học một cách dễ hiểu.";
            }
        }

        $prompt .= "\n\nHãy trả lời bằng tiếng Việt, ngắn gọn nhưng đầy đủ thông tin. Nếu là câu hỏi phức tạp, hãy chia nhỏ để dễ hiểu.";

        return $prompt;
    }

    /**
     * Call Gemini API
     */
    private function callGeminiAPI($prompt)
    {
        $response = Http::timeout(30)->post($this->baseUrl . '?key=' . $this->apiKey, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.8,
                'maxOutputTokens' => 1024,
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception('Gemini API error: ' . $response->body());
        }

        $data = $response->json();

        if (empty($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Empty response from Gemini API');
        }

        $aiMessage = $data['candidates'][0]['content']['parts'][0]['text'];

        return [
            'message' => trim($aiMessage),
            'suggestions' => $this->generateSuggestions($prompt)
        ];
    }

    /**
     * Generate suggestions based on prompt
     */
    private function generateSuggestions($prompt)
    {
        // Travel suggestions
        if (strpos($prompt, 'tour') !== false || strpos($prompt, 'du lịch') !== false) {
            return [
                'Xem thêm tour tương tự',
                'So sánh giá tour',
                'Kiểm tra lịch khởi hành',
                'Tư vấn tour phù hợp'
            ];
        }

        // Math suggestions
        if (strpos($prompt, 'toán') !== false || strpos($prompt, 'mathematics') !== false) {
            return [
                'Giải bài tập khác',
                'Giải thích công thức',
                'Ví dụ thực tế',
                'Bài tập nâng cao'
            ];
        }

        // General suggestions
        return [
            'Hỏi chi tiết hơn',
            'Ví dụ cụ thể',
            'Chủ đề liên quan',
            'Câu hỏi khác'
        ];
    }

    /**
     * Determine if should use AI (existing method)
     */
    public function shouldUseAI($message)
    {
        $message = strtolower($message);

        // Always use AI for complex questions
        $complexIndicators = [
            'tại sao',
            'như thế nào',
            'so sánh',
            'nên chọn',
            'khác nhau',
            'tư vấn',
            'recommend',
            'suggest',
            'giải thích',
            'phân tích'
        ];

        foreach ($complexIndicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return true;
            }
        }

        // Use AI for non-travel topics
        $nonTravelKeywords = [
            'toán',
            'math',
            'tính',
            'calculate',
            '+',
            '-',
            '*',
            '/',
            'khoa học',
            'science',
            'lịch sử',
            'history',
            'nấu ăn',
            'cooking'
        ];

        foreach ($nonTravelKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }

        return strlen($message) > 50; // Long messages need AI
    }
}
