<?php

namespace App\Logging;

use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

class JsonContextFormatter implements FormatterInterface
{
    public function format(LogRecord $record): string
    {
        $data = [
            'message' => $record->message,
            'level' => $record->level->value,
            'level_name' => $record->level->getName(),
            'channel' => $record->channel,
            'datetime' => $record->datetime->format('c'),
        ];

        // Flatten context to top-level for Loki JSON parsing.
        foreach ($record->context as $key => $value) {
            if (array_key_exists($key, $data)) {
                $data['context_'.$key] = $value;
            } else {
                $data[$key] = $value;
            }
        }

        if (!empty($record->extra)) {
            $data['extra'] = $record->extra;
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }

    public function formatBatch(array $records): string
    {
        return implode('', array_map([$this, 'format'], $records));
    }
}
