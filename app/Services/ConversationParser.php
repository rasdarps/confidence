<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class ConversationParser
{
    public function parse(string $message, array $context = []): array
    {
        if (preg_match('/^(.*?\b(?:stock|inventory)(?:\s+(?:balance|value|quantity))?\s+(?:for|of)\s+)(?:product\s+)?(?!["\']|(?:customer|supplier|category|account|product|all|today|yesterday|this|last|overall)\b|\d{4}-)([^"\']+)$/i', trim($message), $productMatches)) {
            $parts = preg_split('/(?=\s+(?:today|yesterday|this|last|on\s+\d{4}-|from\s+\d{4}-|in\s+(?:january|february|march|april|may|june|july|august|september|october|november|december))\b)/i', $productMatches[2], 2);
            $productName = rtrim(trim($parts[0]), '?');
            if ($productName !== '') {
                $message = $productMatches[1].'product "'.$productName.'"'.($parts[1] ?? '');
            }
        }
        $text = strtolower(trim(preg_replace('/\s+/', ' ', $message)));
        $text = preg_replace('/(["\']).*?\1/', '""', $text);
        if (preg_match('/\b(delete|cancel|create|update|pay|record|transfer money|change price)\b/', $text)) {
            throw new InvalidArgumentException('Chat is read-only. Use the transaction screens to make changes.');
        }
        if (preg_match('/\b(profit|forecast|predict|overdue|tax breakdown)\b/', $text)) {
            throw new InvalidArgumentException('That report is not supported in chat yet. Please use the existing report screens.');
        }

        $intent = match (true) {
            (bool) preg_match('/\b(low stock|below.*stock|restock)\b/', $text) => 'low_stock',
            (bool) preg_match('/\b(best.selling|top products|most sold)\b/', $text) => 'top_products',
            (bool) preg_match('/\b(stock|inventory)\b/', $text) => preg_match('/\b(value|values|valuation|worth)\b/', $text) ? 'stock_value' : 'stock_balance',
            !empty($context['intent']) && in_array($context['intent'], ['stock_balance', 'stock_value'], true) && (bool) preg_match('/\b(value|values|valuation|worth)\b/', $text) => 'stock_value',
            (bool) preg_match('/\b(expense|expenses|spend|spending|spent)\b/', $text) => 'expenses',
            (bool) preg_match('/\b(bank|banking|deposits|withdrawals)\b/', $text) => preg_match('/\bbalances?\b/', $text) ? 'bank_balances' : 'bank_transactions',
            (bool) preg_match('/\b(?:payments|paid)\b.*\bsupplier\b|\bsupplier\b.*\b(?:payments|paid)\b/', $text) => 'supplier_payments',
            (bool) preg_match('/\b(supplier|suppliers|purchase|purchases|payables)\b/', $text) => preg_match('/\b(owe|owed|owing|balance|balances|outstanding|debt|payables)\b/', $text) ? 'supplier_balance' : 'purchases',
            (bool) preg_match('/\b(owe|owed|owing|balance|balances|outstanding|debt|receivables)\b/', $text) => 'customer_balance',
            (bool) preg_match('/\b(collect|collected|collections|receipts|payments)\b/', $text) => 'collections',
            (bool) preg_match('/\b(sale|sales|customer|customers)\b/', $text) => 'sales',
            default => null,
        };
        if (!$intent && !preg_match('/^(what about |and |only |compare |break |by |today|yesterday|this |last |from |in |on |\d{4}-)/', $text)) {
            throw new InvalidArgumentException('Please ask about sales, collections, customer or supplier balances, purchases, stock, banking, or expenses.');
        }
        if (!$intent && empty($context['intent'])) {
            throw new InvalidArgumentException('Which report would you like: sales, collections, purchases, balances, stock, banking, or expenses?');
        }
        $sameIntent = !$intent || $intent === ($context['intent'] ?? null);
        if (in_array($intent, ['stock_balance', 'stock_value'], true) && in_array($context['intent'] ?? null, ['stock_balance', 'stock_value'], true)) {
            $sameIntent = true;
        }
        $result = $sameIntent ? $context : [];
        $result['intent'] = $intent ?? $context['intent'];
        $result['group'] = preg_match('/\b(by|break.*by) customer\b/', $text) ? 'customer' : ($result['group'] ?? null);
        if (preg_match('/\b(by|break.*by) categor(?:y|ies)\b/', $text)) {
            $result['group'] = 'category';
        }
        if (preg_match('/\b(by|break.*by) supplier\b/', $text)) {
            $result['group'] = 'supplier';
        }
        foreach (['customer', 'supplier', 'category', 'account', 'product', 'outlet'] as $entity) {
            $patternEntity = $entity === 'outlet' ? '(?:outlet|branch|location)' : $entity;
            if (preg_match('/\b'.$patternEntity.'\s+["\']([^"\']+)["\']/i', $message, $matches)) {
                $result[$entity] = trim($matches[1]);
            } elseif (preg_match('/\b(?:for|from|to|at|in) '.$patternEntity.'\s+(.+?)(?=\s+(?:today|yesterday|this|last|from \d|between|on \d|in |for \d)|[?.!]|$)/i', $message, $matches)) {
                $result[$entity] = trim($matches[1]);
            }
            if (!isset($result[$entity]) && preg_match('/\b'.$patternEntity.'\s+(?!""|balances?\b|outstanding\b|sales\b|purchases\b|payments?\b|credit\b|today\b|yesterday\b|this\b|last\b|in\b|from\b|on\b|owe\b|owes\b|debt\b|transactions?\b)[a-z0-9]+/', $text)) {
                throw new InvalidArgumentException('Please specify the '.$entity.' name in quotes, for example '.$entity.' "Example Name".');
            }
        }
        if (in_array($result['intent'], ['stock_balance', 'stock_value'], true) && preg_match('/\b(?:stock|inventory)(?:\s+(?:balance|value|quantity))?\s+(?:of|for)\s+["\']([^"\']+)["\']/i', $message, $matches)) {
            $result['product'] = trim($matches[1]);
        }
        if (preg_match('/\bfor\s+(?!customer\b|supplier\b|category\b|account\b|product\b|outlet\b|branch\b|location\b|today\b|yesterday\b|this\b|last\b|all\b|january\b|february\b|march\b|april\b|may\b|june\b|july\b|august\b|september\b|october\b|november\b|december\b)[a-z]+/', $text)
            || preg_match('/\bwhat does\s+(?!customer\b|supplier\b|the customer\b|the supplier\b)[a-z]+.*\bowe\b/', $text)) {
            throw new InvalidArgumentException('Specify the filter type and quote its name, for example sales for customer "Example Customer" today.');
        }
        if (preg_match('/\b(all customers|all suppliers|all accounts|overall)\b/', $text)) {
            unset($result['customer'], $result['supplier'], $result['account']);
        }
        if (preg_match('/\ball products\b/', $text)) {
            unset($result['product']);
        }
        if (preg_match('/\b(all stock|all inventory|overall)\b/', $text)) {
            unset($result['product'], $result['category']);
        }
        if (preg_match('/\b(all outlets|all branches|all locations|entire enterprise|consolidated)\b/', $text)) {
            unset($result['outlet']);
        }
        $compare = (bool) preg_match('/\b(compare|comparison|versus|vs)\b/', $text);
        $parts = $compare ? preg_split('/\b(?:with|versus|vs)\b/', $text, 2) : [$text];
        $range = $this->dates($parts[0]);
        $comparisonRange = isset($parts[1]) ? $this->dates($parts[1]) : null;
        if ($compare && isset($parts[1]) && !$comparisonRange && !str_contains($parts[1], 'previous period')) {
            throw new InvalidArgumentException('Please specify the comparison dates or say "previous period".');
        }
        if ($compare && !$range && !isset($context['from'])) {
            throw new InvalidArgumentException('Specify the main date range first, for example "Compare sales this month with last month".');
        }
        if ($range) {
            [$result['from'], $result['to']] = $range;
            $result['date_requested'] = true;
        } elseif (preg_match('/\b(all time|ever)\b/', $text)) {
            $result['from'] = null;
            $result['to'] = null;
            $result['date_requested'] = true;
        } elseif (!array_key_exists('from', $result)) {
            $result['from'] = CarbonImmutable::today(config('app.timezone'))->toDateString();
            $result['to'] = $result['from'];
            $result['date_requested'] = false;
        }
        $result['payment_status'] = preg_match('/\b(only credit|credit sales)\b/', $text) ? 'credit' : ($result['payment_status'] ?? null);
        if (preg_match('/\b(all sales|all purchases)\b/', $text)) {
            $result['payment_status'] = null;
        }
        if (preg_match_all('/\b(deposits|withdrawals|transfers)\b/', $text, $movementMatches)) {
            $types = [];
            foreach ($movementMatches[1] as $type) {
                $types = array_merge($types, match ($type) {
                    'deposits' => ['deposit'],
                    'withdrawals' => ['withdrawal'],
                    'transfers' => ['transfer_in', 'transfer_out'],
                });
            }
            $result['bank_type'] = array_values(array_unique($types));
        } elseif (preg_match('/\b(all bank|bank transactions)\b/', $text)) {
            unset($result['bank_type']);
        }
        $result['compare'] = $compare;
        if ($result['compare'] && $comparisonRange) {
            $result['comparison'] = $comparisonRange;
        } elseif ($result['compare']) {
            if (!$result['from'] || !$result['to']) {
                throw new InvalidArgumentException('Choose a date range before comparing periods.');
            }
            $start = CarbonImmutable::parse($result['from']);
            $days = $start->diffInDays(CarbonImmutable::parse($result['to'])) + 1;
            $result['comparison'] = [$start->subDays($days)->toDateString(), $start->subDay()->toDateString()];
        } else {
            unset($result['comparison']);
        }
        return $result;
    }

    private function dates(string $text): ?array
    {
        $today = CarbonImmutable::today(config('app.timezone'));
        if (preg_match_all('/\b(?:this|last) (?:week|month|year)\b|\b(?:january|february|march|april|may|june|july|august|september|october|november|december)\s+\d{4}\b/', $text) > 1) {
            throw new InvalidArgumentException('Use a single period, or separate comparison periods with "with".');
        }
        if (preg_match_all('/\b\d{4}-\d{2}-\d{2}\b/', $text, $matches)) {
            if (count($matches[0]) > 2) {
                throw new InvalidArgumentException('Use one date or one date range per question.');
            }
            $dates = array_map(function ($date) {
                [$year, $month, $day] = array_map('intval', explode('-', $date));
                if (!checkdate($month, $day, $year)) {
                    throw new InvalidArgumentException('That date is invalid. Use YYYY-MM-DD.');
                }
                return $date;
            }, $matches[0]);
            $end = $dates[1] ?? $dates[0];
            if ($dates[0] > $end) {
                throw new InvalidArgumentException('The start date must be before the end date.');
            }
            return [$dates[0], $end];
        }
        if (preg_match('/\b(this|last) (week|month|year)\b/', $text, $matches)) {
            $date = $matches[1] === 'last' ? $today->{'sub'.ucfirst($matches[2])}() : $today;
            $start = $date->{'startOf'.ucfirst($matches[2])}();
            $end = $matches[1] === 'this' ? $today : $date->{'endOf'.ucfirst($matches[2])}();
            return [$start->toDateString(), $end->toDateString()];
        }
        if (preg_match('/\blast (\d{1,3}) days\b/', $text, $matches)) {
            if ((int) $matches[1] < 1) {
                throw new InvalidArgumentException('Choose at least one day.');
            }
            return [$today->subDays((int) $matches[1] - 1)->toDateString(), $today->toDateString()];
        }
        if (preg_match('/\b(january|february|march|april|may|june|july|august|september|october|november|december)\s+(\d{4})\b/', $text, $matches)) {
            $date = CarbonImmutable::parse('1 '.$matches[1].' '.$matches[2]);
            return [$date->startOfMonth()->toDateString(), $date->endOfMonth()->toDateString()];
        }
        if (str_contains($text, 'yesterday')) {
            return [$today->subDay()->toDateString(), $today->subDay()->toDateString()];
        }
        if (preg_match('/\btoday\b/', $text)) {
            return [$today->toDateString(), $today->toDateString()];
        }
        if (preg_match('/\b(january|february|march|april|may|june|july|august|september|october|november|december|week|month|year|tomorrow)\b|\d{1,4}[\/-]\d{1,2}/', $text)) {
            throw new InvalidArgumentException('Please use today, yesterday, this/last week or month, a month with year (September 2026), or YYYY-MM-DD dates.');
        }
        return null;
    }
}