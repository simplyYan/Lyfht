<?php
class Lyfth {
    private $knowledge = [];
    private $synonyms = [];
    private $context = [];
    private $memoryFile;
    private $contextFile;
    private $enableContext;
    private $enableLearning;
    private $responseVariations;

    public function __construct($options = []) {
        $this->memoryFile = $options['memory_file'] ?? 'lyfth_memory.json';
        $this->contextFile = $options['context_file'] ?? 'lyfth_context.json';
        $this->enableContext = $options['enable_context'] ?? false;
        $this->enableLearning = $options['enable_learning'] ?? false;
        $this->responseVariations = $options['response_variations'] ?? true;

        if ($this->enableLearning && file_exists($this->memoryFile)) {
            $this->knowledge = json_decode(file_get_contents($this->memoryFile), true) ?? [];
        }

        if ($this->enableContext && file_exists($this->contextFile)) {
            $this->context = json_decode(file_get_contents($this->contextFile), true) ?? [];
        }
    }

    public function loadKnowledge($jsonFile) {
        if (file_exists($jsonFile)) {
            $data = json_decode(file_get_contents($jsonFile), true);
            if (is_array($data)) {
                $this->knowledge = array_merge($this->knowledge, $data);
            }
        }
    }

    public function loadSynonyms($jsonFile) {
        if (file_exists($jsonFile)) {
            $data = json_decode(file_get_contents($jsonFile), true);
            if (is_array($data)) {
                $this->synonyms = array_merge($this->synonyms, $data);
            }
        }
    }

    public function ask($question) {
        $originalQuestion = $this->sanitize($question);
        $question = $this->applySynonyms($originalQuestion);

        if ($this->enableContext && !empty($this->context['last_topic'])) {
            $question = $this->context['last_topic'] . ' ' . $question;
        }

        $bestMatch = $this->findBestMatch($question);
        if ($bestMatch) {
            if ($this->enableContext) {
                $this->context['last_topic'] = $bestMatch;
                $this->saveContext();
            }

            $response = $this->knowledge[$bestMatch];
            if (is_array($response) && $this->responseVariations) {
                return $response[array_rand($response)];
            } elseif (is_array($response)) {
                return $response[0];
            } else {
                return $response;
            }
        }

        return "I don't know how to answer that.";
    }

    public function teach($question, $answer) {
        $key = $this->sanitize($question);

        if (!isset($this->knowledge[$key])) {
            $this->knowledge[$key] = [];
        }

        if (is_array($this->knowledge[$key])) {
            $this->knowledge[$key][] = $answer;
        } else {
            $this->knowledge[$key] = [$this->knowledge[$key], $answer];
        }

        if ($this->enableLearning) {
            $this->saveMemory();
        }
    }

    private function sanitize($text) {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $text)));
    }

    private function applySynonyms($text) {
        foreach ($this->synonyms as $canonical => $synonyms) {
            foreach ($synonyms as $syn) {
                $text = preg_replace('/\b' . preg_quote($syn, '/') . '\b/i', $canonical, $text);
            }
        }
        return $text;
    }

    private function findBestMatch($input) {
        $bestMatch = null;
        $lowestDistance = PHP_INT_MAX;
        $inputWords = explode(' ', $input);

        foreach ($this->knowledge as $key => $value) {
            $keyWords = explode(' ', $key);
            $common = array_intersect($inputWords, $keyWords);
            $weight = count($common);

            $distance = levenshtein($input, $key) - ($weight * 2);

            if ($distance < $lowestDistance) {
                $lowestDistance = $distance;
                $bestMatch = $key;
            }
        }

        return $lowestDistance < 10 ? $bestMatch : null; // 10 is a tolerance threshold
    }

    private function saveMemory() {
        file_put_contents($this->memoryFile, json_encode($this->knowledge, JSON_PRETTY_PRINT));
    }

    private function saveContext() {
        file_put_contents($this->contextFile, json_encode($this->context, JSON_PRETTY_PRINT));
    }
}
?>
