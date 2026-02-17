<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CustomerImportConflict extends Model
{
    use HasFactory;

    protected $table = 'customer_import_conflicts';

    protected $fillable = [
        'business_id',
        'import_batch_id',
        'common_customer_id',
        'existing_ledger_id',
        'conflict_type',
        'conflict_description',
        'common_customer_data',
        'existing_customer_data',
        'similarity_score',
        'resolution_status',
        'resolution_action',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
        'result_ledger_id',
        'resolution_data'
    ];

    protected $casts = [
        'common_customer_data' => 'array',
        'existing_customer_data' => 'array',
        'resolution_data' => 'array',
        'similarity_score' => 'decimal:2',
        'resolved_at' => 'datetime'
    ];

    // Conflict type constants
    const TYPE_DUPLICATE_PHONE = 'duplicate_phone';
    const TYPE_DUPLICATE_NAME = 'duplicate_name';
    const TYPE_SIMILAR_CUSTOMER = 'similar_customer';
    const TYPE_LOCATION_MISMATCH = 'location_mismatch';
    const TYPE_DATA_INCONSISTENCY = 'data_inconsistency';

    // Resolution status constants
    const STATUS_PENDING = 'pending';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_IGNORED = 'ignored';
    const STATUS_MANUAL_REVIEW = 'manual_review';

    // Resolution action constants
    const ACTION_SKIP_IMPORT = 'skip_import';
    const ACTION_IMPORT_ANYWAY = 'import_anyway';
    const ACTION_MERGE_DATA = 'merge_data';
    const ACTION_UPDATE_EXISTING = 'update_existing';
    const ACTION_CREATE_NEW = 'create_new';

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function importHistory()
    {
        return $this->belongsTo(CustomerImportHistory::class, 'import_batch_id', 'import_batch_id');
    }

    public function commonCustomer()
    {
        return $this->belongsTo(CustomerLedger::class, 'common_customer_id', 'ledger_id');
    }

    public function existingLedger()
    {
        return $this->belongsTo(Ledger::class, 'existing_ledger_id');
    }

    public function resultLedger()
    {
        return $this->belongsTo(Ledger::class, 'result_ledger_id');
    }

    public static function getConflictTypes()
    {
        return [
            self::TYPE_DUPLICATE_PHONE => 'Duplicate Phone Number',
            self::TYPE_DUPLICATE_NAME => 'Duplicate Name',
            self::TYPE_SIMILAR_CUSTOMER => 'Similar Customer',
            self::TYPE_LOCATION_MISMATCH => 'Location Mismatch',
            self::TYPE_DATA_INCONSISTENCY => 'Data Inconsistency'
        ];
    }

    public static function getResolutionActions()
    {
        return [
            self::ACTION_SKIP_IMPORT => 'Skip Import',
            self::ACTION_IMPORT_ANYWAY => 'Import Anyway',
            self::ACTION_MERGE_DATA => 'Merge Data',
            self::ACTION_UPDATE_EXISTING => 'Update Existing',
            self::ACTION_CREATE_NEW => 'Create New'
        ];
    }

    public function getConflictTypeLabelAttribute()
    {
        $types = self::getConflictTypes();
        return $types[$this->conflict_type] ?? 'Unknown';
    }

    public function getResolutionActionLabelAttribute()
    {
        $actions = self::getResolutionActions();
        return $actions[$this->resolution_action] ?? 'Not Set';
    }

    public function resolve($action, $notes = null, $resolvedBy = null)
    {
        $this->update([
            'resolution_status' => self::STATUS_RESOLVED,
            'resolution_action' => $action,
            'resolution_notes' => $notes,
            'resolved_by' => $resolvedBy ?? (Auth::check() ? Auth::user()->name : 'System'),
            'resolved_at' => now()
        ]);

        return $this->executeResolution();
    }

    private function executeResolution()
    {
        switch ($this->resolution_action) {
            case self::ACTION_SKIP_IMPORT:
                return $this->skipImport();

            case self::ACTION_IMPORT_ANYWAY:
                return $this->importAnyway();

            case self::ACTION_MERGE_DATA:
                return $this->mergeData();

            case self::ACTION_UPDATE_EXISTING:
                return $this->updateExisting();

            case self::ACTION_CREATE_NEW:
                return $this->createNew();

            default:
                throw new \Exception('Invalid resolution action: ' . $this->resolution_action);
        }
    }

    private function skipImport()
    {
        // Just mark as resolved, no action needed
        return [
            'action' => 'skipped',
            'message' => 'Import skipped as requested'
        ];
    }

    private function importAnyway()
    {
        $commonCustomer = $this->commonCustomer;
        if (!$commonCustomer) {
            throw new \Exception('Common customer not found');
        }

        $ledger = Ledger::createFromCommonCustomer(
            $commonCustomer,
            $this->business_id,
            $this->import_batch_id,
            $this->resolved_by
        );

        $this->update(['result_ledger_id' => $ledger->id]);

        return [
            'action' => 'imported',
            'ledger_id' => $ledger->id,
            'message' => 'Customer imported despite conflicts'
        ];
    }

    private function mergeData()
    {
        $commonCustomer = $this->commonCustomer;
        $existingLedger = $this->existingLedger;

        if (!$commonCustomer || !$existingLedger) {
            throw new \Exception('Required data not found for merge');
        }

        // Merge logic: update existing ledger with common customer data
        $mergedData = [
            'name' => $this->chooseBestValue($existingLedger->name, $commonCustomer->ledger_name),
            'contact' => $this->chooseBestValue($existingLedger->contact, $commonCustomer->contact_number),
            'district' => $this->chooseBestValue($existingLedger->district, $commonCustomer->district),
            'sub_district' => $this->chooseBestValue($existingLedger->sub_district, $commonCustomer->sub_district),
            'village' => $this->chooseBestValue($existingLedger->village, $commonCustomer->village),
            'landmark' => $this->chooseBestValue($existingLedger->landmark, $commonCustomer->landmark),
            'common_customer_id' => $commonCustomer->ledger_id,
            'import_source' => Ledger::IMPORT_SOURCE_COMMON,
            'is_imported' => true,
            'last_synced_at' => now()
        ];

        $existingLedger->update($mergedData);
        $this->update(['result_ledger_id' => $existingLedger->id]);

        return [
            'action' => 'merged',
            'ledger_id' => $existingLedger->id,
            'message' => 'Data merged successfully'
        ];
    }

    private function updateExisting()
    {
        $commonCustomer = $this->commonCustomer;
        $existingLedger = $this->existingLedger;

        if (!$commonCustomer || !$existingLedger) {
            throw new \Exception('Required data not found for update');
        }

        // Update existing ledger with common customer data
        $existingLedger->update([
            'name' => $commonCustomer->ledger_name,
            'contact' => $commonCustomer->contact_number,
            'district' => $commonCustomer->district,
            'sub_district' => $commonCustomer->sub_district,
            'village' => $commonCustomer->village,
            'landmark' => $commonCustomer->landmark,
            'location' => $commonCustomer->getFullLocationAttribute(),
            'common_customer_id' => $commonCustomer->ledger_id,
            'import_source' => Ledger::IMPORT_SOURCE_COMMON,
            'is_imported' => true,
            'last_synced_at' => now()
        ]);

        $this->update(['result_ledger_id' => $existingLedger->id]);

        return [
            'action' => 'updated',
            'ledger_id' => $existingLedger->id,
            'message' => 'Existing customer updated'
        ];
    }

    private function createNew()
    {
        $commonCustomer = $this->commonCustomer;
        if (!$commonCustomer) {
            throw new \Exception('Common customer not found');
        }

        // Create new ledger with modified name to avoid conflict
        $newName = $commonCustomer->ledger_name . ' (Imported)';

        $ledger = new Ledger([
            'business_id' => $this->business_id,
            'name' => $newName,
            'contact' => $commonCustomer->contact_number,
            'location' => $commonCustomer->getFullLocationAttribute(),
            'ledger_type' => Ledger::mapCommonTypeToLedgerType($commonCustomer->type),
            'district' => $commonCustomer->district,
            'sub_district' => $commonCustomer->sub_district,
            'village' => $commonCustomer->village,
            'landmark' => $commonCustomer->landmark,
            'common_customer_id' => $commonCustomer->ledger_id,
            'import_source' => Ledger::IMPORT_SOURCE_COMMON,
            'imported_at' => now(),
            'is_imported' => true,
            'imported_by' => $this->resolved_by
        ]);

        $ledger->save();
        $ledger->generateBusinessCustomerCode();

        $this->update(['result_ledger_id' => $ledger->id]);

        return [
            'action' => 'created_new',
            'ledger_id' => $ledger->id,
            'message' => 'New customer created with modified name'
        ];
    }

    private function chooseBestValue($existing, $new)
    {
        // Choose the longer/more complete value
        if (empty($existing)) return $new;
        if (empty($new)) return $existing;

        return strlen($new) > strlen($existing) ? $new : $existing;
    }
}
