<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductImport implements ToCollection, SkipsEmptyRows, WithHeadingRow
{
    public int $imported = 0;

    public int $failed = 0;

    public array $errors = [];

    public function __construct(private readonly int $userId)
    {
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->normalizeRow($row);

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'category_name' => ['required', 'string', 'max:255'],
                'unit_name' => ['required', 'string', 'max:255'],
                'stock_level' => ['nullable', 'numeric', 'min:0'],
            ]);

            if ($validator->fails()) {
                $this->registerFailure($rowNumber, $validator->errors()->all());
                continue;
            }

            $category = Category::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($data['category_name'])])
                ->first();

            if (!$category) {
                $this->registerFailure($rowNumber, ["Category '{$data['category_name']}' was not found."]);
                continue;
            }

            $unit = Unit::query()
                ->whereRaw('LOWER(name) = ?', [Str::lower($data['unit_name'])])
                ->first();

            if (!$unit) {
                $this->registerFailure($rowNumber, ["Unit '{$data['unit_name']}' was not found."]);
                continue;
            }

            if (Product::query()->where('name', $data['name'])->exists()) {
                $this->registerFailure($rowNumber, ["Product '{$data['name']}' already exists."]);
                continue;
            }

            try {
                DB::transaction(function () use ($data, $category, $unit) {
                    $product = Product::create([
                        'name' => $data['name'],
                        'category_id' => $category->id,
                        'unit_id' => $unit->id,
                        'stock_level' => (float) ($data['stock_level'] ?? 0),
                        'created_by' => $this->userId,
                    ]);

                    $product->barcode = Product::generateBarcode($product->name, $product->id, $product->created_at);
                    $product->saveQuietly();
                });

                $this->imported++;
            } catch (\Throwable $e) {
                $this->registerFailure($rowNumber, [$e->getMessage()]);
            }
        }
    }

    public function summary(): array
    {
        return [
            'imported' => $this->imported,
            'failed' => $this->failed,
            'errors' => $this->errors,
        ];
    }

    private function normalizeRow(array|Collection $row): array
    {
        $rowArray = $row instanceof Collection ? $row->toArray() : $row;

        return [
            'name' => trim((string) ($rowArray['name'] ?? '')),
            'category_name' => trim((string) ($rowArray['category_name'] ?? $rowArray['category'] ?? '')),
            'unit_name' => trim((string) ($rowArray['unit_name'] ?? $rowArray['unit'] ?? '')),
            'stock_level' => $this->normalizeNumeric($rowArray['stock_level'] ?? 0),
        ];
    }

    private function normalizeNumeric(mixed $value): float|int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return is_numeric($value) ? $value + 0 : 0;
    }

    private function registerFailure(int $rowNumber, array $messages): void
    {
        $this->failed++;
        $this->errors[] = [
            'row' => $rowNumber,
            'messages' => $messages,
        ];
    }
}