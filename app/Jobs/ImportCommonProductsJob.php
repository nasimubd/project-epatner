<?php

namespace App\Jobs;

use App\Models\CommonProduct;
use App\Models\CommonCategory;
use App\Models\CommonUnit;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use League\Csv\Reader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ImportCommonProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $userId;
    public $timeout = 3600; // 1 hour timeout
    public $tries = 1;
    protected $jobId;
    protected $dbConnection; // Renamed from $connection to avoid conflict with Queueable trait

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, int $userId, string $jobId = null, string $dbConnection = 'mysql_common')
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->jobId = $jobId;
        $this->dbConnection = $dbConnection;
    }


    /**
     * Process CSV file
     */
    protected function processCsvFile(string $fileContent): array
    {
        // Convert encoding if needed
        $fileContent = mb_convert_encoding($fileContent, 'UTF-8', mb_detect_encoding($fileContent, 'UTF-8, ISO-8859-1', true));

        $csv = Reader::createFromString($fileContent);
        $csv->setHeaderOffset(0);
        return iterator_to_array($csv->getRecords());
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startTime = now();
        $totalRows = 0;
        $processedRows = 0;
        $failedRows = 0;
        $errors = [];


        try {
            // Ensure database connection is properly configured
            if (!Config::has('database.connections.' . $this->dbConnection)) {
                throw new \Exception('Database connection [' . $this->dbConnection . '] not configured.');
            }

            // Get file content
            if (!Storage::exists($this->filePath)) {
                throw new \Exception('Import file not found: ' . $this->filePath);
            }

            $fileContent = Storage::get($this->filePath);
            if (empty($fileContent)) {
                throw new \Exception('Import file is empty: ' . $this->filePath);
            }

            // Determine file type and parse accordingly
            $extension = pathinfo($this->filePath, PATHINFO_EXTENSION);
            Log::info('Processing file', ['extension' => $extension, 'path' => $this->filePath]);

            if (in_array($extension, ['xlsx', 'xls'])) {
                // Process Excel file
                $rows = $this->processExcelFile($this->filePath);
            } else {
                // Process CSV file
                $rows = $this->processCsvFile($fileContent);

                try {
                    $csv = Reader::createFromString($fileContent);
                    $csv->setHeaderOffset(0);
                    $rows = iterator_to_array($csv->getRecords());

                    // Debug the CSV headers and first row
                    $headers = $csv->getHeader();
                    Log::info('CSV headers', ['headers' => $headers]);

                    if (empty($rows)) {
                        throw new \Exception('No data rows found in CSV file');
                    }

                    Log::info('First row of CSV data', ['first_row' => reset($rows)]);
                } catch (\Exception $e) {
                    Log::error('Error parsing CSV', [
                        'error' => $e->getMessage(),
                        'file_content_sample' => substr($fileContent, 0, 200)
                    ]);
                    throw $e;
                }
            }

            $totalRows = count($rows);
            if ($totalRows === 0) {
                throw new \Exception('No data rows found in import file');
            }

            // Update initial progress with total rows
            if ($this->jobId) {
                Cache::put('import_progress_' . $this->jobId, [
                    'progress' => 0,
                    'status' => 'processing',
                    'message' => 'Processing ' . $totalRows . ' rows',
                    'total_rows' => $totalRows,
                    'processed_rows' => 0
                ], 3600);
            }

            // Process in chunks to avoid memory issues
            $chunkIndex = 0;
            foreach (array_chunk($rows, 100) as $chunk) {
                DB::connection($this->dbConnection)->beginTransaction();

                try {
                    foreach ($chunk as $row) {
                        $result = $this->processRow($row);

                        if ($result['success']) {
                            $processedRows++;
                        } else {
                            $failedRows++;
                            $errors[] = $result['error'];

                            // Limit error reporting to avoid huge error lists
                            if (count($errors) >= 100) {
                                $errors[] = "Too many errors, truncating error list...";
                                break 2;
                            }
                        }

                        // Update progress every 10 rows
                        if ($processedRows % 10 === 0 && $this->jobId) {
                            $progress = $totalRows > 0 ? round(($processedRows / $totalRows) * 100) : 0;
                            Cache::put('import_progress_' . $this->jobId, [
                                'progress' => $progress,
                                'status' => 'processing',
                                'message' => "Processed $processedRows of $totalRows rows",
                                'total_rows' => $totalRows,
                                'processed_rows' => $processedRows
                            ], 3600);
                        }
                    }

                    DB::connection($this->dbConnection)->commit();

                    // Update progress after each chunk
                    $chunkIndex++;
                    if ($this->jobId) {
                        $progress = $totalRows > 0 ? round(($processedRows / $totalRows) * 100) : 0;
                        Cache::put('import_progress_' . $this->jobId, [
                            'progress' => $progress,
                            'status' => 'processing',
                            'message' => "Processed chunk $chunkIndex",
                            'total_rows' => $totalRows,
                            'processed_rows' => $processedRows
                        ], 3600);
                    }
                } catch (\Exception $e) {
                    DB::connection($this->dbConnection)->rollBack();
                    Log::error('Error processing chunk in product import: ' . $e->getMessage());
                    $errors[] = 'Error processing batch: ' . $e->getMessage();
                }
            }

            // Clean up the temporary file
            Storage::delete($this->filePath);

            // Update final progress
            if ($this->jobId) {
                Cache::put('import_progress_' . $this->jobId, [
                    'progress' => 100,
                    'status' => 'completed',
                    'message' => "Import completed. Processed $processedRows rows with $failedRows failures.",
                    'total_rows' => $totalRows,
                    'processed_rows' => $processedRows,
                    'failed_rows' => $failedRows,
                    'errors' => array_slice($errors, 0, 10) // Only include first 10 errors in cache
                ], 3600);
            }

            // Log completion instead of notification
            Log::info('Import completed', [
                'job' => 'Common Products Import',
                'total_rows' => $totalRows,
                'processed_rows' => $processedRows,
                'failed_rows' => $failedRows,
                'duration_seconds' => now()->diffInSeconds($startTime)
            ]);
        } catch (\Exception $e) {
            Log::error('Error in product import job: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Update progress with error
            if ($this->jobId) {
                Cache::put('import_progress_' . $this->jobId, [
                    'progress' => 0,
                    'status' => 'failed',
                    'message' => 'Fatal error: ' . $e->getMessage(),
                    'total_rows' => $totalRows,
                    'processed_rows' => $processedRows
                ], 3600);
            }

            // Log error instead of notification
            Log::error('Import failed', [
                'job' => 'Common Products Import',
                'total_rows' => $totalRows,
                'processed_rows' => $processedRows,
                'failed_rows' => $failedRows,
                'error' => $e->getMessage(),
                'duration_seconds' => now()->diffInSeconds($startTime)
            ]);
        }
    }

    /**
     * Process a single row from the import file
     */
    protected function processRow(array $row): array
    {
        try {
            // Validate required fields
            if (empty($row['product_name']) || empty($row['category_id']) || empty($row['unit_id'])) {
                return [
                    'success' => false,
                    'error' => 'Missing required fields for row: ' . json_encode($row)
                ];
            }

            $row['product_name'] = $this->sanitizeString($row['product_name']);
            if (!empty($row['barcode'])) {
                $row['barcode'] = $this->sanitizeString($row['barcode']);
            }

            // Check if category and unit exist - use direct queries instead of relationships
            $categoryExists = CommonCategory::on($this->dbConnection)
                ->where('category_id', $row['category_id'])
                ->exists();

            $unitExists = CommonUnit::on($this->dbConnection)
                ->where('unit_id', $row['unit_id'])
                ->exists();

            if (!$categoryExists) {
                return [
                    'success' => false,
                    'error' => "Category ID {$row['category_id']} does not exist for product {$row['product_name']}"
                ];
            }

            if (!$unitExists) {
                return [
                    'success' => false,
                    'error' => "Unit ID {$row['unit_id']} does not exist for product {$row['product_name']}"
                ];
            }

            // Check for duplicate barcode if provided
            if (!empty($row['barcode'])) {
                $existingProduct = CommonProduct::on($this->dbConnection)
                    ->where('barcode', $row['barcode'])
                    ->first();

                if ($existingProduct) {
                    return [
                        'success' => false,
                        'error' => "Barcode {$row['barcode']} already exists for product {$existingProduct->product_name}"
                    ];
                }
            }

            // Create the product without the image first
            $product = new CommonProduct();
            $product->setConnection($this->dbConnection);
            $product->product_name = $row['product_name'];
            $product->barcode = $row['barcode'] ?? null;
            $product->category_id = $row['category_id'];
            $product->unit_id = $row['unit_id'];
            $product->save();

            // Process image if URL is provided
            if (!empty($row['image_url'])) {
                $this->processImage($product, $row['image_url']);
            }

            return ['success' => true];
        } catch (\Exception $e) {
            // Debug Step 10: Check exception in processRow
            dd([
                'exception_in_process_row' => true,
                'exception_message' => $e->getMessage(),
                'exception_trace' => $e->getTraceAsString(),
                'connection_at_exception' => $this->dbConnection
            ]);

            Log::error('Error processing row: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error processing row: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sanitize a string to ensure it's compatible with the database
     */
    protected function sanitizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Remove or replace problematic characters
        $value = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $value); // Remove non-printable ASCII
        $value = preg_replace('/[^\p{L}\p{N}\s\-\_\.\,\;\:\!\?\(\)\'\"]/u', '', $value); // Keep only letters, numbers, and common punctuation

        return trim($value);
    }

    /**
     * Process and save image from URL
     */
    protected function processImage(CommonProduct $product, string $imageUrl): void
    {
        // Debug Step 11: Check connection in processImage method
        dd([
            'process_image_called' => true,
            'connection_in_process_image' => $this->dbConnection,
            'product_id' => $product->product_id,
            'image_url' => $imageUrl
        ]);

        try {
            // Get image content
            $imageContent = file_get_contents($imageUrl);

            if ($imageContent) {
                $image = Image::make($imageContent);

                // Resize if necessary
                if ($image->width() > 1200 || $image->height() > 1200) {
                    $image->resize(1200, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }

                // Convert to base64 and save
                $imageData = base64_encode($image->encode('jpg', 40)->getEncoded());

                // Debug Step 12: Check connection before updating image
                dd([
                    'before_image_update' => true,
                    'connection_to_use' => $this->dbConnection,
                    'product_id' => $product->product_id,
                    'image_data_length' => strlen($imageData)
                ]);

                // Use direct query to update the image
                DB::connection($this->dbConnection)->update(
                    "UPDATE tbl_common_product SET image = FROM_BASE64(?) WHERE product_id = ?",
                    [$imageData, $product->product_id]
                );
            }
        } catch (\Exception $e) {
            // Debug Step 13: Check exception in processImage
            dd([
                'exception_in_process_image' => true,
                'exception_message' => $e->getMessage(),
                'exception_trace' => $e->getTraceAsString(),
                'connection_at_exception' => $this->dbConnection
            ]);

            Log::error('Error processing image: ' . $e->getMessage());
        }
    }
    /**
     * Process Excel file
     */
    protected function processExcelFile(string $filePath): array
    {
        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load(Storage::path($filePath));
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = [];

            $headerRow = $worksheet->getRowIterator(1, 1)->current();
            $cellIterator = $headerRow->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $headers = [];
            foreach ($cellIterator as $cell) {
                $headers[] = $cell->getValue();
            }

            // Debug the headers
            Log::info('Excel headers', ['headers' => $headers]);

            // Make sure we have required headers
            if (
                !in_array('product_name', $headers) ||
                !in_array('category_id', $headers) ||
                !in_array('unit_id', $headers)
            ) {
                throw new \Exception('Required columns missing in import file. Needed: product_name, category_id, unit_id');
            }

            foreach ($worksheet->getRowIterator(2) as $row) {
                $rowData = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $i = 0;
                foreach ($cellIterator as $cell) {
                    if (isset($headers[$i])) {
                        $rowData[$headers[$i]] = $cell->getValue();
                    }
                    $i++;
                }

                if (!empty($rowData['product_name'])) {
                    $rows[] = $rowData;
                }
            }

            // Debug the first row
            if (!empty($rows)) {
                Log::info('First row of data', ['first_row' => $rows[0]]);
            } else {
                Log::warning('No data rows found in import file');
            }

            return $rows;
        } catch (\Exception $e) {
            Log::error('Error processing Excel file', [
                'error' => $e->getMessage(),
                'file_path' => $filePath
            ]);
            throw $e;
        }
    }
}
