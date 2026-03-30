<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';
    protected static ?string $navigationGroup = '\u2699\ufe0f Settings & Tools';
    protected static ?string $navigationLabel = 'Backup Manager';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.pages.backup-manager';

    // Only super admin can see this
    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fullDatabaseBackup')
                ->label('Full Database Backup')
                ->icon('heroicon-o-circle-stack')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Download Full Database Backup')
                ->modalDescription('সম্পূর্ণ ডাটাবেস SQL ফাইল হিসেবে ডাউনলোড হবে।')
                ->action(fn () => $this->downloadFullDatabaseBackup()),

            Action::make('fullWebsiteBackup')
                ->label('Full Website Backup (.zip)')
                ->icon('heroicon-o-archive-box')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Download Full Website Backup')
                ->modalDescription('সম্পূর্ণ প্রজেক্ট ফাইল ও ডাটাবেস একটি ZIP ফাইলে ডাউনলোড হবে।')
                ->action(fn () => $this->downloadFullWebsiteBackup()),
        ];
    }

    /**
     * Full DB backup (SQL dump)
     */
    public function downloadFullDatabaseBackup()
    {
        $filename = 'full-db-backup-' . now()->format('Y-m-d_H-i-s') . '.sql';
        $sql = $this->generateSqlDump();

        return response()->streamDownload(
            fn () => print($sql),
            $filename,
            ['Content-Type' => 'application/sql']
        );
    }

    /**
     * Single client data backup
     */
    public function downloadClientBackup(int $clientId)
    {
        $client = Client::findOrFail($clientId);
        $filename = 'client-' . $client->id . '-' . str_replace(' ', '-', $client->shop_name) . '-' . now()->format('Y-m-d') . '.sql';
        $sql = $this->generateClientSqlDump($client);

        return response()->streamDownload(
            fn () => print($sql),
            $filename,
            ['Content-Type' => 'application/sql']
        );
    }

    /**
     * Full Website Backup as ZIP
     */
    public function downloadFullWebsiteBackup()
    {
        $zipPath = storage_path('app/backup-temp/website-backup-' . now()->format('Y-m-d_H-i-s') . '.zip');
        File::ensureDirectoryExists(dirname($zipPath));

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Notification::make()->danger()->title('Backup Failed')->body('Could not create ZIP file.')->send();
            return;
        }

        // Add database dump
        $zip->addFromString('database/backup.sql', $this->generateSqlDump());

        // Add key source folders (not vendor, node_modules)
        $basePath = base_path();
        $includeDirs = ['app', 'config', 'database/migrations', 'resources/views', 'routes', 'public'];

        foreach ($includeDirs as $dir) {
            $fullDir = $basePath . '/' . $dir;
            if (!File::isDirectory($fullDir)) continue;

            $files = File::allFiles($fullDir);
            foreach ($files as $file) {
                $relativePath = $dir . '/' . $file->getRelativePathname();
                $zip->addFile($file->getRealPath(), $relativePath);
            }
        }

        // Add storage/app/public (uploaded files)
        $storagePath = storage_path('app/public');
        if (File::isDirectory($storagePath)) {
            $files = File::allFiles($storagePath);
            foreach (array_slice($files, 0, 500) as $file) { // max 500 files to avoid timeout
                $relativePath = 'storage/' . $file->getRelativePathname();
                $zip->addFile($file->getRealPath(), $relativePath);
            }
        }

        $zip->close();

        return response()->download($zipPath, basename($zipPath), [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate full SQL dump
     */
    private function generateSqlDump(): string
    {
        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $dbKey = 'Tables_in_' . $dbName;

        $sql = "-- EcommerceMessanger Full Database Backup\n";
        $sql .= "-- Generated: " . now()->toDateTimeString() . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$dbKey;
            $sql .= $this->dumpTable($tableName);
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }

    /**
     * Generate SQL dump for a single client's data
     */
    private function generateClientSqlDump(Client $client): string
    {
        $sql = "-- Client Backup: {$client->shop_name} (ID: {$client->id})\n";
        $sql .= "-- Generated: " . now()->toDateTimeString() . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        // Tables related to a client
        $clientTables = [
            'clients' => ['id' => $client->id],
            'products' => ['client_id' => $client->id],
            'orders' => ['client_id' => $client->id],
            'categories' => ['client_id' => $client->id],
            'coupons' => ['client_id' => $client->id],
            'conversations' => ['client_id' => $client->id],
            'pages' => ['client_id' => $client->id],
        ];

        foreach ($clientTables as $table => $condition) {
            try {
                $sql .= $this->dumpTableWhere($table, $condition);
            } catch (\Exception $e) {
                $sql .= "-- Skipped table: $table\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }

    /**
     * Dump a full table
     */
    private function dumpTable(string $tableName): string
    {
        $sql = "-- Table: $tableName\n";
        $sql .= "DROP TABLE IF EXISTS `$tableName`;\n";
        $create = DB::select("SHOW CREATE TABLE `$tableName`");
        $sql .= $create[0]->{'Create Table'} . ";\n\n";

        $rows = DB::table($tableName)->get();
        if ($rows->count() > 0) {
            foreach ($rows as $row) {
                $values = array_map(fn ($v) => is_null($v) ? 'NULL' : "'" . addslashes($v) . "'", (array) $row);
                $sql .= "INSERT INTO `$tableName` VALUES (" . implode(', ', $values) . ");\n";
            }
        }

        return $sql . "\n";
    }

    /**
     * Dump a table with WHERE clause
     */
    private function dumpTableWhere(string $tableName, array $where): string
    {
        $sql = "-- Table: $tableName (filtered)\n";

        $query = DB::table($tableName);
        foreach ($where as $col => $val) {
            $query->where($col, $val);
        }
        $rows = $query->get();

        if ($rows->count() === 0) return $sql . "-- (no rows)\n\n";

        foreach ($rows as $row) {
            $values = array_map(fn ($v) => is_null($v) ? 'NULL' : "'" . addslashes($v) . "'", (array) $row);
            $sql .= "INSERT INTO `$tableName` VALUES (" . implode(', ', $values) . ");\n";
        }

        return $sql . "\n";
    }

    public function getTitle(): string
    {
        return '🛡️ Backup Manager';
    }
}
