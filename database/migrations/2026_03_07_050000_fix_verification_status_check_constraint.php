<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix the status CHECK constraint on identity_verifications table.
 * SQLite enum() creates a CHECK constraint limiting values to 'pending', 'approved', 'rejected'.
 * We need to add 'returned' as a valid value.
 *
 * In SQLite, you can't ALTER a CHECK constraint, so we recreate the table.
 * In PostgreSQL, ALTER COLUMN ... TYPE is used (MODIFY COLUMN is MySQL-only syntax).
 *
 * NOTE: Uses a named class (not anonymous "return new class") to ensure
 * compatibility with Composer's --optimize-autoloader classmap generation.
 */
class FixVerificationStatusCheckConstraint extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: must recreate the table to change CHECK constraint
            DB::statement('PRAGMA foreign_keys=OFF');

            // 1. Get all existing data
            $rows = DB::select('SELECT * FROM identity_verifications');

            // 3. Rename current table
            DB::statement('ALTER TABLE identity_verifications RENAME TO _identity_verifications_old');

            // 4. Recreate with VARCHAR status (no CHECK constraint)
            // Build the CREATE TABLE manually to be safe
            DB::statement("\n                CREATE TABLE identity_verifications (\n                    id INTEGER PRIMARY KEY AUTOINCREMENT,\n                    user_id INTEGER NOT NULL,\n                    document_type VARCHAR(255) NOT NULL,\n                    document_front VARCHAR(255),\n                    document_front_status VARCHAR(20) DEFAULT 'pending',\n                    document_front_rejection_reason TEXT,\n                    document_back VARCHAR(255),\n                    document_back_status VARCHAR(20) DEFAULT 'pending',\n                    document_back_rejection_reason TEXT,\n                    selfie VARCHAR(255),\n                    selfie_status VARCHAR(20) DEFAULT 'pending',\n                    selfie_rejection_reason TEXT,\n                    professional_document VARCHAR(255),\n                    professional_document_type VARCHAR(30),\n                    professional_document_status VARCHAR(20) DEFAULT 'pending',\n                    professional_document_rejection_reason TEXT,\n                    status VARCHAR(20) DEFAULT 'pending',\n                    rejection_reason TEXT,\n                    admin_message TEXT,\n                    submitted_at DATETIME,\n                    reviewed_at DATETIME,\n                    reviewed_by INTEGER,\n                    payment_id VARCHAR(255),\n                    payment_amount DECIMAL(8,2) DEFAULT 0,\n                    payment_status VARCHAR(20) DEFAULT 'pending',\n                    payment_method VARCHAR(50),\n                    created_at DATETIME,\n                    updated_at DATETIME,\n                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,\n                    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL\n                )\n            ");

            // 5. Insert old data back, mapping column names properly
            foreach ($rows as $row) {
                $rowArray = (array) $row;
                // Only insert columns that exist in the new table
                $newCols = DB::select("PRAGMA table_info('identity_verifications')");
                $newColNames = array_map(fn($c) => $c->name, $newCols);

                $filteredData = [];
                foreach ($newColNames as $col) {
                    if (array_key_exists($col, $rowArray)) {
                        $filteredData[$col] = $rowArray[$col];
                    }
                }

                if (!empty($filteredData)) {
                    $placeholders = implode(', ', array_fill(0, count($filteredData), '?'));
                    $colList = implode(', ', array_map(fn($c) => "\"$c\"", array_keys($filteredData)));
                    DB::statement(
                        "INSERT INTO identity_verifications ($colList) VALUES ($placeholders)",
                        array_values($filteredData)
                    );
                }
            }

            // 6. Drop old table
            DB::statement('DROP TABLE _identity_verifications_old');

            DB::statement('PRAGMA foreign_keys=ON');

        } elseif ($driver === 'pgsql') {
            // PostgreSQL: ALTER COLUMN syntax (MODIFY COLUMN is MySQL-only)
            // Change type and default separately
            DB::statement("ALTER TABLE identity_verifications ALTER COLUMN status TYPE VARCHAR(20)");
            DB::statement("ALTER TABLE identity_verifications ALTER COLUMN status SET DEFAULT 'pending'");

        } else {
            // MySQL: MODIFY COLUMN syntax
            DB::statement("ALTER TABLE identity_verifications MODIFY COLUMN status VARCHAR(20) DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        // No rollback needed - VARCHAR is more permissive than enum
    }
}