<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class BackupController extends Controller
{
    public function index()
    {
        $folder = storage_path('app/backups');

        if (!File::exists($folder)) {
            File::makeDirectory($folder, 0755, true);
        }

        $backups = collect(File::files($folder))
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => round($file->getSize() / 1024 / 1024, 2),
                'date' => date('d/m/Y H:i:s', $file->getMTime()),
            ])
            ->sortByDesc('date')
            ->values();

        return Inertia::render('Respaldos/Index', [
            'backups' => $backups,
            'backup_path' => $folder,
        ]);
    }

    public function backup()
    {
        $database = database_path('database.sqlite');

        if (!File::exists($database)) {
            return back()->withErrors([
                'backup' => 'No existe la base de datos.',
            ]);
        }

        $folder = storage_path('app/backups');

        if (!File::exists($folder)) {
            File::makeDirectory($folder, 0755, true);
        }

        $fileName = 'SOTANO_POS_' . now()->format('Y-m-d_H-i-s') . '.sqlite';

        File::copy($database, $folder . DIRECTORY_SEPARATOR . $fileName);

        return back()->with('success', 'Respaldo creado correctamente.');
    }
    public function restore($file)
    {
    $folder = storage_path('app/backups');
    $backupFile = $folder . DIRECTORY_SEPARATOR . $file;
    $database = database_path('database.sqlite');

    if (!File::exists($backupFile)) {
        return back()->withErrors([
            'backup' => 'El respaldo no existe.',
        ]);
    }

    File::copy($backupFile, $database);

    return redirect()->route('respaldos')
        ->with('success', 'Base de datos restaurada correctamente.');
    }
    public function destroy($file)
    {
    $folder = storage_path('app/backups');
    $backupFile = $folder . DIRECTORY_SEPARATOR . $file;

    if (!File::exists($backupFile)) {
        return back()->withErrors([
            'backup' => 'El respaldo no existe.',
        ]);
    }

    File::delete($backupFile);

    return back()->with('success', 'Respaldo eliminado correctamente.');
    }
}
